<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_issue_worklog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'updated_at_jira' => 'datetime',
        'time_spent_seconds' => 'integer',
        'is_deleted' => 'boolean',
        'metadata' => 'array',
    ];

    public function issue()
    {
        return $this->belongsTo(jira_issue::class, 'jira_issue_id');
    }

    public function user()
    {
        return $this->belongsTo(jira_user::class, 'jira_user_id');
    }
}
