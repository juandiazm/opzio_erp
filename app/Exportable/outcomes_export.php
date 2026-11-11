<?php

namespace App\Exportable;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class outcomes_export implements WithMultipleSheets
{
    public function __construct(private Builder $query)
    {
    }

    public function sheets(): array
    {
        return [
            new outcomes_export_summary(clone $this->query),
            new outcomes_export_details(clone $this->query),
        ];
    }
}
