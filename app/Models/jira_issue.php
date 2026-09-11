<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_issue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'story_points' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'estimated_hours_manual' => 'boolean',
        'original_estimate_seconds' => 'integer',
        'time_spent_seconds' => 'integer',
        'jira_created_at' => 'datetime',
        'jira_updated_at' => 'datetime',
        'jira_resolved_at' => 'datetime',
        'due_date' => 'date',
        'labels' => 'array',
        'components' => 'array',
        'sprints' => 'array',
        'raw_fields' => 'array',
    ];

    public function connection()
    {
        return $this->belongsTo(jira_connection::class, 'jira_connection_id');
    }

    public function project()
    {
        return $this->belongsTo(jira_project::class, 'jira_project_id');
    }

    public function assignee()
    {
        return $this->belongsTo(jira_user::class, 'assignee_jira_user_id');
    }

    public function reporter()
    {
        return $this->belongsTo(jira_user::class, 'reporter_jira_user_id');
    }

    public function worklogs()
    {
        return $this->hasMany(jira_issue_worklog::class, 'jira_issue_id');
    }

    public function epic()
    {
        return $this->belongsTo(self::class, 'epic_jira_issue_id');
    }
}
