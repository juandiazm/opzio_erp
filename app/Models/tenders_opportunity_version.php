<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_opportunity_version extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'changed_fields' => 'array',
        'raw_payload' => 'array',
        'observed_at' => 'datetime',
    ];

    public function opportunity()
    {
        return $this->belongsTo(tenders_opportunity::class, 'tenders_opportunity_id');
    }
}
