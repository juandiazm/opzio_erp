<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sms_log extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(client::class, 'client_id');
    }

    public function resendOf()
    {
        return $this->belongsTo(self::class, 'resend_of_id');
    }
}