<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_project extends Model
{
    protected $fillable = [
        'host_id',
        'key',
        'name',
        'path',
        'environment',
        'enabled',
        'php_version',
        'fpm_pool',
        'fpm_status_url',
        'nginx_access_log',
        'nginx_error_log',
        'attribution_mode',
        'metadata',
    ];

    protected $casts = [
        'host_id' => 'integer',
        'enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public function host()
    {
        return $this->belongsTo(servers_host::class, 'host_id');
    }
}
