<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_pipeline_item extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function opportunity()
    {
        return $this->belongsTo(tenders_opportunity::class, 'tenders_opportunity_id');
    }
}
