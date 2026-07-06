<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class GenericImport implements ToCollection
{
    public $rows;

    public function collection(Collection $collection)
    {
        $this->rows = $collection;
    }
}