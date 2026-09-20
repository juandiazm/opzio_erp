<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_document_chunk extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(tenders_document::class, 'tenders_document_id');
    }
}
