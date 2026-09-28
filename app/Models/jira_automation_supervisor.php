<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_automation_supervisor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean'];

    public function project()
    {
        return $this->belongsTo(jira_automation_project::class, 'jira_automation_project_id');
    }

    public function user()
    {
        return $this->belongsTo(user::class, 'user_id');
    }
}