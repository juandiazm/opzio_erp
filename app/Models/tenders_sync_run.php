<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_sync_run extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cursor_from' => 'datetime',
        'cursor_to' => 'datetime',
        'parameters' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(tenders_connection::class, 'tenders_connection_id');
    }
}
