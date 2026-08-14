<?php

namespace App\Domain\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class observability_storage_sample extends Model
{
    protected $fillable = [
        'project_id',
        'agent_id',
        'sampled_at',
        'total_bytes',
        'files',
        'directories',
        'scan_duration_ms',
        'breakdown',
        'metadata',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'sampled_at' => 'datetime',
        'breakdown' => 'array',
        'metadata' => 'array',
    ];
}
