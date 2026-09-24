<?php

namespace App\Services;

use App\Models\whatsapp_message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WhatsappMediaStorage
{
    public const DISK = 'erp_media';
    public const DIRECTORY = 'whatsapp/media';

    public function storeMessageMedia(whatsapp_message $message): array
    {
        return $this->storeMedia(is_array($message->media) ? $message->media : [], (int) $message->id);
    }

    public function storeMedia(array $media, int $messageId): array
    {
        $filesystem = Storage::disk(self::DISK);
        $storedMedia = [];

        foreach ($media as $index => $item) {
            if (!is_array($item)) {
                $storedMedia[] = $item;
                continue;
            }

            $storagePath = trim((string) ($item['storage_path'] ?? ''));
            if ($this->isSafeStoragePath($storagePath) && $filesystem->exists($storagePath)) {
                $item['storage_path'] = $storagePath;
                $item['storage_disk'] = self::DISK;
                $storedMedia[] = $item;
                continue;
            }

            $url = trim((string) ($item['url'] ?? ''));
            if (!$this->isTwilioMediaUrl($url)) {
                $storedMedia[] = $item;
                continue;
            }

            try {
                $providerResponse = Http::timeout(30)
                    ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
                    ->get($url);
                if (!$providerResponse->successful()) {
                    throw new \RuntimeException('Twilio respondio con HTTP '.$providerResponse->status());
                }

                $contentType = $this->normalizeContentType(
                    $providerResponse->header('Content-Type') ?: ($item['content_type'] ?? null)
                );
                $extension = $this->extensionFor($contentType);
                $storagePath = $this->buildStoragePath($messageId, (int) $index, $extension);
                if (!$filesystem->put($storagePath, $providerResponse->body())) {
                    throw new \RuntimeException('No fue posible escribir el archivo en storage.');
                }

                $item['storage_path'] = $storagePath;
                $item['storage_disk'] = self::DISK;
                $item['content_type'] = $contentType;
            } catch (\Throwable $exception) {
                info('WhatsappMediaStorage error: '.$exception->getMessage(), [
                    'message_id' => $messageId,
                    'media_index' => (int) $index,
                ]);
            }

            $storedMedia[] = $item;
        }

        return array_values($storedMedia);
    }

    public function isTwilioMediaUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';

        return is_array($parts)
            && ($parts['scheme'] ?? '') === 'https'
            && (in_array($host, ['api.twilio.com', 'media.twilio.com', 'media.twiliocdn.com'], true)
                || (str_starts_with($host, 'api.') && str_ends_with($host, '.twilio.com')));
    }

    public function isSafeStoragePath(string $path): bool
    {
        return $path !== ''
            && !str_contains($path, '..')
            && !str_starts_with($path, '/')
            && !str_contains($path, '\\');
    }

    public function normalizeContentType($value): string
    {
        $contentType = strtolower(trim(explode(';', (string) $value)[0]));
        if (in_array($contentType, ['text/html', 'application/xhtml+xml'], true)) {
            return 'application/octet-stream';
        }

        return preg_match('/^[a-z0-9.+-]+\/[a-z0-9.+-]+$/', $contentType)
            ? $contentType
            : 'application/octet-stream';
    }

    public function extensionFor(string $contentType): string
    {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'video/mp4' => 'mp4',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ][$contentType] ?? 'bin';
    }

    public function buildStoragePath(int $messageId, int $mediaIndex, string $extension): string
    {
        return self::DIRECTORY.'/'.$messageId.'/'.$mediaIndex.'.'.$extension;
    }
}
