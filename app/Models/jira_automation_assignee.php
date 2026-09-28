<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_automation_assignee extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_unassigned' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(jira_automation_project::class, 'jira_automation_project_id');
    }

    public function jiraUser()
    {
        return $this->belongsTo(jira_user::class, 'jira_user_id');
    }
}