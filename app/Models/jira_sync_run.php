<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_sync_run extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cursor_from' => 'datetime',
        'cursor_to' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(jira_connection::class, 'jira_connection_id');
    }
}
