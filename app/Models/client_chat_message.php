<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class client_chat_message extends Model
{
    use HasFactory;
    protected $appends = ['created_at_string'];
    public function getCreatedAtStringAttribute(){
        //if created at is less than 24 hours ago
        if(Carbon::parse($this->created_at)->diffInHours() < 24){
            return Carbon::parse($this->created_at)->format('h:i A');
        }else{
            return Carbon::parse($this->created_at)->format('d/m/Y h:i A');
        }
    }
}
