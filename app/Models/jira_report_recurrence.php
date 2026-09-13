<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class jira_report_recurrence extends Model
{
    protected $guarded = [];

    protected $casts = [
        'frequency_value' => 'integer',
        'execution_day' => 'integer',
        'range_value' => 'integer',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (jira_report_recurrence $recurrence): void {
            if (blank($recurrence->unique_id)) {
                $recurrence->unique_id = (string) Str::uuid();
            }
        });
    }

    public function templateReport()
    {
        return $this->belongsTo(jira_report::class, 'template_report_id');
    }

    public function reports()
    {
        return $this->hasMany(jira_report::class, 'recurrence_id');
    }
}