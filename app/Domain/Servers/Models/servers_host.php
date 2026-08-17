<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_host extends Model
{
    protected $fillable = [
        'key',
        'name',
        'hostname',
        'environment',
        'config_version',
        'enabled',
        'metadata',
    ];

    protected $casts = [
        'config_version' => 'integer',
        'enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public function projects()
    {
        return $this->hasMany(servers_project::class, 'host_id');
    }

    public function agents()
    {
        return $this->hasMany(servers_agent::class, 'host_id');
    }
}
