<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class whatsapp_message extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'media' => 'array',
        'content_variables' => 'array',
        'raw_payload' => 'array',
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(whatsapp_conversation::class, 'conversation_id');
    }

    public function client()
    {
        return $this->belongsTo(client::class, 'client_id');
    }
}