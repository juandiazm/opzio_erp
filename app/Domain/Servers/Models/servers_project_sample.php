<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_project_sample extends Model
{
    protected $fillable = [
        'project_id',
        'agent_id',
        'sampled_at',
        'attribution_mode',
        'cpu_percent',
        'memory_rss_bytes',
        'memory_pss_bytes',
        'process_count',
        'fpm_active_processes',
        'fpm_idle_processes',
        'fpm_listen_queue',
        'fpm_max_listen_queue',
        'fpm_max_children_reached',
        'fpm_slow_requests',
        'storage_total_bytes',
        'storage_files',
        'storage_directories',
        'storage_scan_duration_ms',
        'metadata',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'sampled_at' => 'datetime',
        'metadata' => 'array',
    ];
}
