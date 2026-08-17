<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_event extends Model
{
    protected $fillable = [
        'host_id',
        'project_id',
        'agent_id',
        'event_type',
        'severity',
        'occurred_at',
        'message',
        'context',
    ];

    protected $casts = [
        'host_id' => 'integer',
        'project_id' => 'integer',
        'occurred_at' => 'datetime',
        'context' => 'array',
    ];
}
