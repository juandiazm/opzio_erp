<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_sync_state extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cursor_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(tenders_connection::class, 'tenders_connection_id');
    }
}
