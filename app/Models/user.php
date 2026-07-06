<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class user extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ['password'];
    protected $appends = ['complete_name'];
    public function getCompleteNameAttribute(){
        return $this->name.(($this->last_name!=null)?' '.$this->last_name:'');
    }
}
