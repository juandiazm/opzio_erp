<?php

namespace App\Imports;

use App\Services\Outcomes\OutcomeImportService;

class outcomes_import
{
    public function import(string $path, string $source, int $userId): array
    {
        return app(OutcomeImportService::class)->import($path, $source, $userId);
    }
}