<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class jira_report extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'data_sources' => 'array',
        'data_snapshot' => 'array',
        'report_data' => 'array',
        'generated_at' => 'datetime',
        'last_emailed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (jira_report $report): void {
            if (blank($report->unique_id)) {
                $report->unique_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'unique_id';
    }

    public function creator()
    {
        return $this->belongsTo(user::class, 'created_by_user_id');
    }

    public function connection()
    {
        return $this->belongsTo(jira_connection::class, 'jira_connection_id');
    }

    public function project()
    {
        return $this->belongsTo(jira_project::class, 'jira_project_id');
    }

    public function epic()
    {
        return $this->belongsTo(jira_issue::class, 'jira_epic_issue_id');
    }
}
