<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_project extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'story_point_hours_multiplier' => 'decimal:2',
        'jira_updated_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(jira_connection::class, 'jira_connection_id');
    }

    public function issues()
    {
        return $this->hasMany(jira_issue::class, 'jira_project_id');
    }

    public function clients()
    {
        return $this->belongsToMany(client::class, 'jira_project_clients', 'jira_project_id', 'client_id')->withPivot(['is_primary', 'notes'])->withTimestamps();
    }

    public function licenses()
    {
        return $this->belongsToMany(license::class, 'jira_project_licenses', 'jira_project_id', 'license_id')->withTimestamps();
    }
}
