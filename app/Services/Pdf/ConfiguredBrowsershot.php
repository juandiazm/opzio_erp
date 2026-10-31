<?php

namespace App\Services\Pdf;

use Spatie\Browsershot\Browsershot;

class ConfiguredBrowsershot extends Browsershot
{
    protected function getWindowsEnv(): array
    {
        $environment = parent::getWindowsEnv();

        foreach (['NODE_OPTIONS', 'PUPPETEER_EXECUTABLE_PATH', 'PUPPETEER_PRODUCT'] as $name) {
            $value = getenv($name);
            if ($value !== false) {
                $environment[$name] = $value;
            }
        }

        foreach ($this->nodeEnvVars as $name => $value) {
            $environment[$name] = $value;
        }

        if ($this->nodeModulePath !== null) {
            $environment['NODE_PATH'] = $this->nodeModulePath;
        }

        return $environment;
    }
}
