<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_document extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'source_uploaded_at' => 'datetime',
        'size_bytes' => 'integer',
        'extracted_text' => 'string',
    ];

    public function opportunity()
    {
        return $this->belongsTo(tenders_opportunity::class, 'tenders_opportunity_id');
    }

    public function chunks()
    {
        return $this->hasMany(tenders_document_chunk::class, 'tenders_document_id');
    }
}
