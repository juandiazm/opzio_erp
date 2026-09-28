<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ai_development_event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'attempt' => 'integer',
        'metadata' => 'array',
    ];

    public function execution()
    {
        return $this->belongsTo(ai_development_execution::class, 'execution_id');
    }

    public function approval()
    {
        return $this->belongsTo(ai_development_approval::class, 'approval_id');
    }
}