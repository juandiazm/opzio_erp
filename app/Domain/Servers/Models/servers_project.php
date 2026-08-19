<?php

namespace App\Domain\Servers\Models;

use App\Models\client;
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
        'client_id',
        'notifications_enabled',
        'notification_recipients_initialized',
        'notification_name',
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
        'client_id' => 'integer',
        'enabled' => 'boolean',
        'notifications_enabled' => 'boolean',
        'notification_recipients_initialized' => 'boolean',
        'metadata' => 'array',
    ];

    public function host()
    {
        return $this->belongsTo(servers_host::class, 'host_id');
    }

    public function client()
    {
        return $this->belongsTo(client::class, 'client_id');
    }

    public function notificationRecipients()
    {
        return $this->hasMany(servers_project_notification::class, 'project_id');
    }
}
