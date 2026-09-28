<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_automation_project extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];

    public function jiraProject()
    {
        return $this->belongsTo(jira_project::class, 'jira_project_id');
    }

    public function githubConnection()
    {
        return $this->belongsTo(github_connection::class, 'github_connection_id');
    }

    public function defaultAgent()
    {
        return $this->belongsTo(ai_agent::class, 'default_agent_id');
    }

    public function issueTypes()
    {
        return $this->hasMany(jira_automation_issue_type::class);
    }

    public function assignees()
    {
        return $this->hasMany(jira_automation_assignee::class);
    }

    public function supervisors()
    {
        return $this->hasMany(jira_automation_supervisor::class);
    }

    public function approvals()
    {
        return $this->hasMany(ai_development_approval::class);
    }

    public function executions()
    {
        return $this->hasMany(ai_development_execution::class);
    }
}