<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;

use App\traits\blog_trait;

class blog extends Model
{
    use HasFactory;
    use blog_trait;
    protected $appends = ['created_at_humans', 'updated_at_humans', 'path', 'image_path'];
    public function getCreatedAtHumansAttribute(){
        return Carbon::parse($this->created_at)->diffForHumans();
    }
    public function getUpdatedAtHumansAttribute(){
        return Carbon::parse($this->updated_at)->diffForHumans();
    }
    public function getPathAttribute(){
        return '/blog/'.$this->url;
    } 
    public function getImagePathAttribute(){
        /*get current base url*/
        return Storage::disk('blog_principal_images')->url($this->img);
    }
    public function blog_segments(){
        return $this->hasMany(blog_segment::class, 'blog_id');
    }
}
