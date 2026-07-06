<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class twitter_post extends Model
{
    use HasFactory;
    protected $appends = ['image_url_complete'];
    public function getImageUrlCompleteAttribute()
    {
        return Storage::disk('twitter_post_images')->url($this->image_url);
    }
}
