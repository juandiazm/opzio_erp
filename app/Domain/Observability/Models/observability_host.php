<?php

namespace App\Domain\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class observability_host extends Model
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
        return $this->hasMany(observability_project::class, 'host_id');
    }

    public function agents()
    {
        return $this->hasMany(observability_agent::class, 'host_id');
    }
}
