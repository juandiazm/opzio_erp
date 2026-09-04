<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tenders_context extends Model
{
    protected $table = 'tenders_contexts';

    protected $guarded = [];

    protected $casts = [
        'services' => 'array',
        'technologies' => 'array',
        'sectors' => 'array',
        'geography' => 'array',
        'excluded_terms' => 'array',
        'min_contract_value' => 'float',
        'max_contract_value' => 'float',
        'version' => 'integer',
        'updated_by' => 'integer',
    ];
}
