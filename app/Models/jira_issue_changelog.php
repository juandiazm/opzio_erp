<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_issue_changelog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function issue()
    {
        return $this->belongsTo(jira_issue::class, 'jira_issue_id');
    }

    public function author()
    {
        return $this->belongsTo(jira_user::class, 'author_jira_user_id');
    }
}
