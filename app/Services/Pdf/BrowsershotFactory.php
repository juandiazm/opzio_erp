<?php

namespace App\Services\Pdf;

use RuntimeException;
use Spatie\Browsershot\Browsershot;

class BrowsershotFactory
{
    public function fromHtml(string $html): Browsershot
    {
        $runtimeDir = storage_path('app/runtime/browsershot');
        $runtimeDirectories = [
            $runtimeDir,
            $runtimeDir.'/tmp',
            $runtimeDir.'/home',
            $runtimeDir.'/config',
            $runtimeDir.'/cache',
            $runtimeDir.'/data',
        ];

        foreach ($runtimeDirectories as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
                throw new RuntimeException('Unable to create PDF runtime directory: '.$directory);
            }

            if (! is_writable($directory)) {
                throw new RuntimeException('PDF runtime directory is not writable: '.$directory);
            }
        }

        $nodeModulePath = (string) config('services.pdf.node_module_path');
        $puppeteerManifest = rtrim($nodeModulePath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'puppeteer'.DIRECTORY_SEPARATOR.'package.json';

        if (! is_readable($puppeteerManifest)) {
            throw new RuntimeException('Puppeteer is not readable from the configured Node modules path: '.$nodeModulePath);
        }

        $puppeteerCacheDir = config('services.pdf.puppeteer_cache_dir');
        if ($puppeteerCacheDir && (! is_dir($puppeteerCacheDir) || ! is_readable($puppeteerCacheDir))) {
            throw new RuntimeException('Puppeteer cache directory is missing or unreadable: '.$puppeteerCacheDir);
        }

        $runtimeEnvironment = [
            'HOME' => $runtimeDir.'/home',
            'XDG_CONFIG_HOME' => $runtimeDir.'/config',
            'XDG_CACHE_HOME' => $runtimeDir.'/cache',
            'XDG_DATA_HOME' => $runtimeDir.'/data',
            'TMPDIR' => $runtimeDir.'/tmp',
            'TMP' => $runtimeDir.'/tmp',
            'TEMP' => $runtimeDir.'/tmp',
        ];

        if ($puppeteerCacheDir) {
            $runtimeEnvironment['PUPPETEER_CACHE_DIR'] = $puppeteerCacheDir;
        }

        $browsershot = ConfiguredBrowsershot::html($html)
            ->setNodeBinary(config('services.pdf.node_binary'))
            ->setNodeModulePath($nodeModulePath)
            ->setCustomTempPath($runtimeDir.'/tmp')
            ->setNodeEnv($runtimeEnvironment);

        $chromePath = config('services.pdf.chrome_path');
        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }

        if (config('services.pdf.no_sandbox')) {
            $browsershot->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ]);
        }

        return $browsershot;
    }
}
