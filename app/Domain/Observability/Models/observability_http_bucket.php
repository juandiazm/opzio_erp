<?php

namespace App\Domain\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class observability_http_bucket extends Model
{
    protected $fillable = [
        'project_id',
        'agent_id',
        'bucket_start',
        'bucket_seconds',
        'requests_total',
        'status_2xx',
        'status_3xx',
        'status_4xx',
        'status_5xx',
        'status_499',
        'status_500',
        'status_502',
        'status_503',
        'status_504',
        'request_bytes',
        'response_bytes',
        'latency_count',
        'latency_sum_ms',
        'p50_ms',
        'p95_ms',
        'p99_ms',
        'metadata',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'bucket_start' => 'datetime',
        'bucket_seconds' => 'integer',
        'metadata' => 'array',
    ];
}
