<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class client_user extends Model
{
    use HasFactory, SoftDeletes;
    //Hide password
    protected $hidden = ['password', 'reset_password', 'reset_password_date'];
}
