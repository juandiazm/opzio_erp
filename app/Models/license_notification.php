<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class license_notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'channels' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(client::class, 'client_id');
    }

    public function license()
    {
        return $this->belongsTo(license::class, 'license_id');
    }

    public function tags()
    {
        return $this->belongsToMany(notification_tag::class, 'notification_contact_tag', 'contact_id', 'tag_id');
    }
}
