<?php 
namespace App\traits;
use Spatie\Browsershot\Browsershot;
trait pdf_trait
{
    public function PDF_GenerarPDF($view, $Data, $orientation = 'portrait')
    {
        ini_set('memory_limit', '256M');

        $html = \View::make($view, compact('Data'))->render();
        $html = $this->PDF_InlineLocalAssets($html);

        $browsershot = Browsershot::html($html)
            ->setNodeBinary(config('services.pdf.node_binary', 'node'))
            ->setNodeModulePath(config('services.pdf.node_module_path', base_path('node_modules')))
            ->format('A4')
            ->landscape(strtolower($orientation) === 'landscape')
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->newHeadless()
            ->writeOptionsToFile()
            ->setOption('preferCSSPageSize', true);

        $chromePath = config('services.pdf.chrome_path');
        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }

        if ($view === 'pdf.contract') {
            $browsershot
                ->showBrowserHeaderAndFooter()
                ->headerHtml($this->PDF_ContractHeaderHtml($Data))
                ->footerHtml($this->PDF_ContractFooterHtml());
        }

            if ($view === 'pdf.servers.monthly_report') {
                $browsershot
                    ->showBrowserHeaderAndFooter()
                    ->headerHtml($this->PDF_ServersMonthlyHeaderHtml($Data))
                    ->footerHtml($this->PDF_ServersMonthlyFooterHtml());
            }

        return $browsershot->pdf();
    }

    private function PDF_ContractHeaderHtml($Data = [])
    {
        $contract = is_array($Data['contract'] ?? null) ? $Data['contract'] : [];
        $logo = $this->PDF_AssetDataUri(public_path('images/opzio-logo-wide-purple-transparent.png'));
        $logo = $logo ? htmlspecialchars($logo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $identifier = htmlspecialchars((string) ($contract['unique_id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $date = htmlspecialchars((string) ($contract['date'] ?? now()->format('Y-m-d')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logoHtml = $logo
            ? '<img src="'.$logo.'" alt="Opzio" style="display: inline-block; width: 130px; height: auto;">'
            : '<span style="color: #220245; font-size: 16px; font-weight: bold;">OPZIO</span>';

        return '<div style="box-sizing: border-box; width: 100%; margin: 0; padding: 0 8px;"><div style="box-sizing: border-box; width: 100%; margin: 0; padding: 0 0 6px; border-bottom: 2px solid #220245; font-family: Arial, sans-serif;"><table style="width: 100%; border-collapse: collapse; table-layout: fixed;"><tr><td style="width: 33%; color: #d6d6d6; font-size: 8px; text-align: left; vertical-align: top;">ID: '.$identifier.'</td><td style="width: 34%; text-align: center; vertical-align: top;">'.$logoHtml.'</td><td style="width: 33%; color: #d6d6d6; font-size: 8px; text-align: right; vertical-align: top;">Fecha: '.$date.'</td></tr></table></div></div>';
    }

    private function PDF_ContractFooterHtml()
    {
        return '<div style="box-sizing: border-box; width: 100%; margin: 0; padding: 0 8px;"><table style="box-sizing: border-box; width: 100%; margin: 0; padding: 5px 0 0; border-top: 1px solid #d9d9d9; border-collapse: collapse; color: #777; font-family: Arial, sans-serif; font-size: 8px; white-space: nowrap;"><tr><td style="width: 33%; padding: 5px 0 0; text-align: left;">legal@opzio.co</td><td style="width: 34%; padding: 5px 0 0; text-align: center;">Página <span class="pageNumber"></span> de <span class="totalPages"></span></td><td style="width: 33%; padding: 5px 0 0; text-align: right;">opzio.co</td></tr></table></div>';
    }

    private function PDF_InlineLocalAssets($html)
    {
        $html = preg_replace_callback('/(\bsrc\s*=\s*)(["\'])([^"\']*)\2/i', function (array $matches) {
            $dataUri = $this->PDF_AssetDataUri($matches[3]);

            return $dataUri === null
                ? $matches[0]
                : $matches[1].$matches[2].$dataUri.$matches[2];
        }, $html) ?? $html;

        return preg_replace_callback('/url\(\s*(["\']?)([^"\')]+)\1\s*\)/i', function (array $matches) {
            $dataUri = $this->PDF_AssetDataUri($matches[2]);

            return $dataUri === null
                ? $matches[0]
                : 'url('.$matches[1].$dataUri.$matches[1].')';
        }, $html) ?? $html;
    }

    private function PDF_ServersMonthlyHeaderHtml($Data = [])
    {
        $report = is_array($Data['report'] ?? null) ? $Data['report'] : [];
        $project = is_array($report['project'] ?? null) ? $report['project'] : [];
        $logo = $this->PDF_AssetDataUri(public_path('images/opzio-logo-wide-purple-transparent.png'));
        $logo = $logo ? htmlspecialchars($logo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $projectName = htmlspecialchars((string) ($project['display_name'] ?? $project['name'] ?? 'Proyecto'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $period = htmlspecialchars((string) ($report['period']['label'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logoHtml = $logo
            ? '<img src="'.$logo.'" alt="Opzio" style="display: inline-block; width: 118px; height: auto;">'
            : '<span style="color: #220245; font-size: 16px; font-weight: bold;">OPZIO</span>';

        return '<div style="box-sizing: border-box; width: 100%; margin: 0; padding: 0 8px;"><div style="box-sizing: border-box; width: 100%; margin: 0; padding: 0 0 7px; border-bottom: 2px solid #220245; font-family: Arial, sans-serif;"><table style="width: 100%; border-collapse: collapse; table-layout: fixed;"><tr><td style="width: 33%; color: #220245; font-size: 10px; font-weight: bold; text-align: left; vertical-align: middle;">'.$projectName.'</td><td style="width: 34%; text-align: center; vertical-align: middle;">'.$logoHtml.'</td><td style="width: 33%; color: #220245; font-size: 10px; font-weight: bold; text-align: right; vertical-align: middle;">'.$period.'</td></tr></table></div></div>';
    }

    private function PDF_ServersMonthlyFooterHtml()
    {
        return '<div style="box-sizing: border-box; width: 100%; margin: 0; padding: 5px 8px 0; border-top: 1px solid #d9d9d9; color: #777; font-family: Arial, sans-serif; font-size: 8px; white-space: nowrap;"><table style="width: 100%; border-collapse: collapse;"><tr><td style="width: 33%; padding-top: 5px; text-align: left;">soporte@opzio.co</td><td style="width: 34%; padding-top: 5px; text-align: center;">Página <span class="pageNumber"></span> de <span class="totalPages"></span></td><td style="width: 33%; padding-top: 5px; text-align: right;">opzio.co</td></tr></table></div>';
    }

    private function PDF_AssetDataUri($source)
    {
        $path = html_entity_decode(trim($source), ENT_QUOTES | ENT_HTML5);
        $path = preg_replace('/[?#].*$/', '', $path);

        if (str_starts_with(strtolower($path), 'file://')) {
            $path = substr($path, 7);
            if (preg_match('/^\/[A-Za-z]:[\\\\\/]/', $path)) {
                $path = substr($path, 1);
            }
        }

        $isWindowsPath = preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
        if (!$isWindowsPath && preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)) {
            return null;
        }

        $path = rawurldecode(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
        $path = realpath($path);
        if ($path === false || !$this->PDF_IsAllowedAssetPath($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($path) : false;
        $mime = $mime ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function PDF_IsAllowedAssetPath($path)
    {
        $path = strtolower(str_replace('\\', '/', rtrim($path, "\\/")));

        foreach ([public_path(), storage_path('app/public')] as $root) {
            $root = realpath($root);
            if ($root === false) {
                continue;
            }

            $root = strtolower(str_replace('\\', '/', rtrim($root, "\\/")));
            if ($path === $root || str_starts_with($path, $root.'/')) {
                return true;
            }
        }

        return false;
    }
}