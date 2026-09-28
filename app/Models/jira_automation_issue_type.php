<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_automation_issue_type extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean'];

    public function project()
    {
        return $this->belongsTo(jira_automation_project::class, 'jira_automation_project_id');
    }
}