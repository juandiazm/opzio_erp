<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NormalizeServiceContractTemplateLayout extends Migration
{
    private function serviceTemplate()
    {
        return DB::table('contract_templates')
            ->whereIn('name', [
                'Contrato de prestación de servicios - infraestructura y soporte',
                'Contrato de prestación de servicios - Infraestructura, Soporte y ciberseguridad',
            ])
            ->first();
    }

    public function up()
    {
        $removeManagedChrome = static function ($html) {
            $html = trim((string) $html);
            if ($html === '') {
                return '';
            }

            if (!class_exists(\DOMDocument::class)) {
                $html = preg_replace('/<div[^>]*style=["\'][^"\']*font-size:\s*34px[^"\']*["\'][^>]*>\s*opzio\s*<\/div>\s*<div[^>]*border-top:\s*2px\s+solid\s+#220245[^>]*><\/div>/is', '', $html);
                $html = preg_replace('/<div[^>]*border-top:\s*2px\s+solid\s+#220245[^>]*padding-top:\s*10px[^>]*>.*?<\/div>/is', '', $html);
                return trim((string) $html);
            }

            $document = new \DOMDocument('1.0', 'UTF-8');
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8"><div id="contract-html-root">'.$html.'</div>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
            $root = (new \DOMXPath($document))->query('//*[@id="contract-html-root"]')->item(0);
            if (!$root) {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                return $html;
            }

            $nodes = [];
            foreach ($root->getElementsByTagName('div') as $node) {
                $nodes[] = $node;
            }
            foreach ($nodes as $node) {
                $style = strtolower((string) preg_replace('/\s+/', ' ', trim($node->getAttribute('style'))));
                $text = trim((string) preg_replace('/\s+/', ' ', $node->textContent));
                $hasRightAlignedSpan = false;
                foreach ($node->getElementsByTagName('span') as $span) {
                    if (str_contains(strtolower((string) $span->getAttribute('style')), 'float: right')) {
                        $hasRightAlignedSpan = true;
                        break;
                    }
                }

                $isHeaderBrand = $text === 'opzio'
                    && str_contains($style, 'text-align: center')
                    && str_contains($style, 'font-size: 34px')
                    && str_contains($style, 'color: #220245');
                $isHeaderRule = $text === ''
                    && str_contains($style, 'border-top: 2px solid #220245')
                    && str_contains($style, 'margin: 0 0 28px 0');
                $isFooter = str_contains($style, 'border-top: 2px solid #220245')
                    && str_contains($style, 'padding-top: 10px')
                    && $hasRightAlignedSpan;

                if (($isHeaderBrand || $isHeaderRule || $isFooter) && $node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }

            $result = '';
            for ($child = $root->firstChild; $child; $child = $child->nextSibling) {
                $result .= $document->saveHTML($child);
            }
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return trim($result);
        };

        $template = $this->serviceTemplate();

        if (!$template) {
            return;
        }

        $content = $removeManagedChrome($template->content);
        if ($content !== (string) $template->content) {
            DB::table('contract_templates')
                ->where('id', $template->id)
                ->update([
                    'content' => $content,
                    'version' => ((int) $template->version) + 1,
                    'updated_at' => now(),
                ]);
        }

        DB::table('contracts')
            ->where('contract_template_id', $template->id)
            ->whereNotNull('content')
            ->chunkById(100, function ($contracts) use ($removeManagedChrome) {
                foreach ($contracts as $contract) {
                    $content = $removeManagedChrome($contract->content);
                    if ($content === (string) $contract->content) {
                        continue;
                    }

                    DB::table('contracts')
                        ->where('id', $contract->id)
                        ->update([
                            'content' => $content,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down()
    {
        $template = $this->serviceTemplate();

        if (!$template) {
            return;
        }

        $header = <<<'HTML'
    <div style="text-align: center; font-size: 34px; font-weight: bold; color: #220245; margin: 0 0 18px 0;">opzio</div>
    <div style="border-top: 2px solid #220245; margin: 0 0 28px 0;"></div>
HTML;
        $footer = <<<'HTML'
    <div style="border-top: 2px solid #220245; margin: 28px 0 0 0; padding-top: 10px; color: #222;">
        <span>{{custom.contractor_email}}</span><span style="float: right;">{{custom.contractor_domain}}</span>
    </div>
HTML;

        $content = trim((string) $template->content);
        if (!str_contains($content, 'font-size: 34px')) {
            $content = $header."\n".$content;
        }
        if (!str_contains($content, 'padding-top: 10px')) {
            $content .= "\n".$footer;
        }

        DB::table('contract_templates')
            ->where('id', $template->id)
            ->update([
                'content' => $content,
                'version' => max(1, ((int) $template->version) - 1),
                'updated_at' => now(),
            ]);
    }
}
