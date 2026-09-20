<?php

namespace App\Services\Tenders;

use App\Models\tenders_document;
use App\Models\tenders_opportunity;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class tenders_document_service
{
    public function __construct(private readonly tenders_secop_client $client)
    {
    }

    public function discoverForOpportunity(tenders_opportunity $opportunity, int $limit = 20): array
    {
        if (! $opportunity->source_process_id) {
            return ['discovered' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0];
        }
        $rows = $this->client->forConnection(app(tenders_configuration_service::class)->get())->fetchDocuments($opportunity->source_process_id, $limit);
        $stats = ['discovered' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0];
        foreach ($rows as $row) {
            $documentId = $this->text($row['id_documento'] ?? null);
            $sourceUrl = $this->nestedUrl($row['url_descarga_documento'] ?? null);
            if (! $documentId || ! $sourceUrl) continue;
            $filename = $this->text($row['nombre_archivo'] ?? null) ?: 'document-'.$documentId;
            $extension = strtolower(ltrim($this->text($row['extensi_n'] ?? null) ?: pathinfo($filename, PATHINFO_EXTENSION), '.')) ?: null;
            $allowed = $this->allowedExtensions();
            $values = [
                'tenders_opportunity_id' => $opportunity->id,
                'source' => 'secop2',
                'source_document_id' => $documentId,
                'process_id' => $this->text($row['proceso'] ?? null),
                'filename' => mb_substr($filename, 0, 1000),
                'extension' => $extension,
                'description' => $this->text($row['descripci_n'] ?? null),
                'source_url' => $sourceUrl,
                'source_uploaded_at' => app(tenders_normalization_service::class)->parseDate($row['fecha_carga'] ?? null),
                'size_bytes' => $this->parseInteger($row['tamanno_archivo'] ?? null),
                'status' => in_array($extension, $allowed, true) ? 'discovered' : 'unsupported',
            ];
            $document = tenders_document::query()->where('source', 'secop2')->where('source_document_id', $documentId)->first();
            if (! $document) {
                tenders_document::create($values);
                $outcome = 'created';
            } else {
                $changed = false;
                foreach ($values as $field => $value) {
                    if ($document->{$field} != $value) {
                        $document->{$field} = $value;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $document->save();
                    $outcome = 'updated';
                } else {
                    $outcome = 'unchanged';
                }
            }
            $stats[$outcome]++;
            $stats['discovered']++;
        }

        return $stats;
    }

    public function process(tenders_document $document): array
    {
        $extension = strtolower(ltrim((string) $document->extension, '.'));
        if (! in_array($extension, $this->allowedExtensions(), true)) {
            $document->update(['status' => 'unsupported', 'error' => 'La extension no esta permitida.']);
            return ['status' => 'unsupported', 'chunks' => 0];
        }
        try {
            $content = $this->download($document->source_url);
            if (strlen($content) > $this->maxBytes()) throw new RuntimeException('El documento supera el limite configurado.');
            $sha256 = hash('sha256', $content);
            $safeId = preg_replace('/[^A-Za-z0-9_.-]/', '_', $document->source_document_id) ?: (string) $document->id;
            $relativePath = $document->source.'/'.$safeId.'.'.$extension;
            Storage::disk($this->disk())->put($relativePath, $content);
            $localPath = Storage::disk($this->disk())->path($relativePath);
            $chunks = $this->extractChunks($localPath, $extension);
            $document->update([
                'size_bytes' => strlen($content),
                'mime_type' => $this->mimeType($content, $extension),
                'sha256' => $sha256,
                'status' => $chunks === [] ? 'downloaded' : 'processed',
                'storage_path' => $relativePath,
                'extracted_text' => implode("\n\n", array_column($chunks, 'text')),
                'error' => null,
            ]);
            $document->chunks()->delete();
            foreach ($chunks as $index => $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $index,
                    'page_ref' => $chunk['page_ref'],
                    'text' => $chunk['text'],
                    'text_hash' => hash('sha256', $chunk['text']),
                ]);
            }

            return ['status' => $chunks === [] ? 'downloaded' : 'processed', 'chunks' => count($chunks)];
        } catch (\Throwable $exception) {
            $document->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 1000)]);
            return ['status' => 'failed', 'chunks' => 0, 'error' => $exception->getMessage()];
        }
    }

    private function download(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! in_array($host, config('tenders.allowed_download_hosts', []), true)) {
            throw new RuntimeException('La URL del documento esta fuera de la allowlist SECOP.');
        }
        $response = Http::timeout(60)->withHeaders([
            'User-Agent' => 'Opzio ERP Tenders/1.0',
            'Accept' => 'application/pdf,application/octet-stream,*/*',
        ])->get($url);
        if (! $response->successful()) throw new RuntimeException('La descarga devolvio HTTP '.$response->status().'.');
        return $response->body();
    }

    private function extractChunks(string $path, string $extension): array
    {
        return match ($extension) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            'xlsx' => $this->extractXlsx($path),
            'txt', 'csv' => $this->splitText((string) file_get_contents($path), null),
            default => [],
        };
    }

    private function extractPdf(string $path): array
    {
        $pdf = (new \Smalot\PdfParser\Parser())->parseFile($path);
        $chunks = [];
        foreach ($pdf->getPages() as $index => $page) {
            $chunks = array_merge($chunks, $this->splitText($page->getText(), (string) ($index + 1)));
        }
        return $chunks;
    }

    private function extractDocx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('No fue posible abrir el DOCX.');
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (! $xml) return [];
        $document = new \DOMDocument();
        $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = [];
        foreach ($xpath->query('//w:p') as $paragraph) {
            $text = '';
            foreach ($xpath->query('.//w:t', $paragraph) as $node) $text .= $node->textContent;
            if (trim($text) !== '') $paragraphs[] = trim($text);
        }
        return $this->splitText(implode("\n", $paragraphs), null);
    }

    private function extractXlsx(string $path): array
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);
        $chunks = [];
        foreach ($workbook->getWorksheetIterator() as $worksheet) {
            $rows = [];
            foreach ($worksheet->toArray(null, true, true, true) as $row) {
                $line = implode(' | ', array_filter(array_map(fn ($value): string => trim((string) $value), $row), fn (string $value): bool => $value !== ''));
                if ($line !== '') $rows[] = $line;
            }
            $chunks = array_merge($chunks, $this->splitText(implode("\n", $rows), $worksheet->getTitle()));
        }
        return $chunks;
    }

    private function splitText(string $content, ?string $pageRef, int $chunkSize = 3500): array
    {
        $content = trim($content);
        if ($content === '') return [];
        $chunks = [];
        for ($offset = 0, $length = mb_strlen($content); $offset < $length; $offset += $chunkSize) {
            $text = trim(mb_substr($content, $offset, $chunkSize));
            if ($text !== '') $chunks[] = ['page_ref' => $pageRef, 'text' => $text];
        }
        return $chunks;
    }

    private function allowedExtensions(): array
    {
        return app(tenders_configuration_service::class)->settings()['documents']['allowed_extensions'];
    }

    private function maxBytes(): int
    {
        return (int) app(tenders_configuration_service::class)->settings()['documents']['max_bytes'];
    }

    private function disk(): string
    {
        return (string) config('tenders.document_disk', 'tenders_documents');
    }

    private function mimeType(string $content, string $extension): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($content) ?: match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            default => 'text/plain',
        };
    }

    private function parseInteger(mixed $value): ?int
    {
        $value = preg_replace('/[^0-9]/', '', (string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || is_array($value)) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function nestedUrl(mixed $value): ?string
    {
        return is_array($value) ? $this->text($value['url'] ?? $value['uri'] ?? $value['href'] ?? null) : $this->text($value);
    }
}
