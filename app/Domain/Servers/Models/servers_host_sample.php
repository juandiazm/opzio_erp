<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_host_sample extends Model
{
    protected $fillable = [
        'host_id',
        'agent_id',
        'sampled_at',
        'cpu_percent',
        'load1',
        'load5',
        'load15',
        'memory_total_bytes',
        'memory_available_bytes',
        'swap_total_bytes',
        'swap_free_bytes',
        'network_rx_bytes',
        'network_tx_bytes',
        'disk_total_bytes',
        'disk_free_bytes',
        'disk_used_bytes',
        'disk_used_percent',
        'metadata',
    ];

    protected $casts = [
        'host_id' => 'integer',
        'sampled_at' => 'datetime',
        'metadata' => 'array',
    ];
}
