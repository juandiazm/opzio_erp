<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class whatsapp_conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_inbound_at' => 'datetime',
        'window_expires_at' => 'datetime',
        'last_read_at' => 'datetime',
        'ai_scope' => 'array',
        'ai_last_processed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(client::class, 'client_id');
    }

    public function messages()
    {
        return $this->hasMany(whatsapp_message::class, 'conversation_id');
    }
}