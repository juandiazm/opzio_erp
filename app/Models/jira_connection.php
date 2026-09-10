<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class jira_connection extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function syncRuns()
    {
        return $this->hasMany(jira_sync_run::class, 'jira_connection_id');
    }

    public function projects()
    {
        return $this->hasMany(jira_project::class, 'jira_connection_id');
    }

    public function users()
    {
        return $this->hasMany(jira_user::class, 'jira_connection_id');
    }

    public function reports()
    {
        return $this->hasMany(jira_report::class, 'jira_connection_id');
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }
}
