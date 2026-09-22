<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class notification_tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function contacts()
    {
        return $this->belongsToMany(license_notification::class, 'notification_contact_tag', 'tag_id', 'contact_id');
    }
}