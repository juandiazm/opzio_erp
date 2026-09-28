<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ai_development_execution extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'attempt' => 'integer',
        'ci_attempts' => 'integer',
        'main_ci_attempts' => 'integer',
        'consecutive_failures' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'qa_delivered_at' => 'datetime',
        'qa_last_feedback_at' => 'datetime',
        'done_detected_at' => 'datetime',
        'context' => 'array',
    ];

    public function issue()
    {
        return $this->belongsTo(jira_issue::class, 'jira_issue_id');
    }

    public function project()
    {
        return $this->belongsTo(jira_automation_project::class, 'jira_automation_project_id');
    }

    public function approval()
    {
        return $this->belongsTo(ai_development_approval::class, 'approval_id');
    }

    public function agent()
    {
        return $this->belongsTo(ai_agent::class, 'agent_id');
    }

    public function events()
    {
        return $this->hasMany(ai_development_event::class, 'execution_id');
    }

    public function environmentBranch(string $environment = 'qa'): string
    {
        if ($environment === 'qa') {
            return $this->base_branch ?: 'qa';
        }

        return data_get($this->context, 'promotion_stage') === 'qa_sync_pipeline'
            ? ($this->base_branch ?: 'qa')
            : 'main';
    }
}