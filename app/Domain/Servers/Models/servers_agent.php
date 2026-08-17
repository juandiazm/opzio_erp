<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_agent extends Model
{
    protected $fillable = [
        'host_id',
        'agent_id',
        'version',
        'commit_sha',
        'config_version',
        'last_seen_at',
        'spool_bytes',
        'spool_batches',
        'uptime_seconds',
        'collection_errors',
        'enabled',
    ];

    protected $casts = [
        'host_id' => 'integer',
        'config_version' => 'integer',
        'last_seen_at' => 'datetime',
        'spool_bytes' => 'integer',
        'spool_batches' => 'integer',
        'uptime_seconds' => 'integer',
        'collection_errors' => 'array',
        'enabled' => 'boolean',
    ];

    public function host()
    {
        return $this->belongsTo(servers_host::class, 'host_id');
    }
}
