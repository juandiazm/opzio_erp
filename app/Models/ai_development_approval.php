<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ai_development_approval extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'story_point_estimate' => 'decimal:2',
        'decided_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    public function issue()
    {
        return $this->belongsTo(jira_issue::class, 'jira_issue_id');
    }

    public function project()
    {
        return $this->belongsTo(jira_automation_project::class, 'jira_automation_project_id');
    }

    public function decider()
    {
        return $this->belongsTo(user::class, 'decided_by_user_id');
    }

    public function selectedAgent()
    {
        return $this->belongsTo(ai_agent::class, 'selected_agent_id');
    }

    public function execution()
    {
        return $this->hasOne(ai_development_execution::class, 'approval_id');
    }
}