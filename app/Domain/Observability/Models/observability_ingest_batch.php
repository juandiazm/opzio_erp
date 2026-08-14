<?php

namespace App\Domain\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class observability_ingest_batch extends Model
{
    protected $fillable = [
        'batch_id',
        'agent_id',
        'captured_at',
        'payload_hash',
        'accepted_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];
}
