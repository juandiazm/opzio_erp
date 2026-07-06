<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class outcome extends Model
{
    use HasFactory, SoftDeletes;
    protected $appends = ['created_at_string', 'date_string'];
    public function getCreatedAtStringAttribute(){
        return Carbon::parse($this->created_at)->format('Y-m-d H:i');
    }
    public function getDateStringAttribute(){
        return Carbon::parse($this->date)->format('Y-m-d');
    }
}
