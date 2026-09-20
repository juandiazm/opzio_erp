<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_outbox_event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
    ];
}
