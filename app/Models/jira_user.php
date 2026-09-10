<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jira_user extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(jira_connection::class, 'jira_connection_id');
    }

    public function mapping()
    {
        return $this->hasOne(jira_user_mapping::class, 'jira_user_id');
    }
}
