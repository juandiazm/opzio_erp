<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_user_mapping extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function jiraUser()
    {
        return $this->belongsTo(jira_user::class, 'jira_user_id');
    }

    public function user()
    {
        return $this->belongsTo(user::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
