<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mail_log extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'mail_data' => 'array',
        'to' => 'array',
    ];

    public function attachments()
    {
        return $this->hasMany(mail_log_attachment::class, 'mail_log_id');
    }
}
