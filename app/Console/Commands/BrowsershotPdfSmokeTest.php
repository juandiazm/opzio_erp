<?php

namespace App\Console\Commands;

use App\Services\Pdf\BrowsershotFactory;
use Illuminate\Console\Command;
use RuntimeException;

class BrowsershotPdfSmokeTest extends Command
{
    protected $signature = 'browsershot:smoke-test';

    protected $description = 'Genera un PDF real con la instalación configurada de Browsershot';

    public function handle(BrowsershotFactory $browsershotFactory): int
    {
        $pdf = $browsershotFactory
            ->fromHtml('<!doctype html><html><body><h1>Browsershot smoke test</h1></body></html>')
            ->format('A4')
            ->showBackground()
            ->newHeadless()
            ->timeout(60)
            ->pdf();

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Browsershot returned output that is not a PDF.');
        }

        $this->info('Browsershot generated a PDF successfully ('.strlen($pdf).' bytes).');

        return self::SUCCESS;
    }
}
