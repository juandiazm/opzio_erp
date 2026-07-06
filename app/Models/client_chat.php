<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class client_chat extends Model
{
    use HasFactory;
    protected $appends = ['chat_name', 'created_at_string', 'updated_at_string', 'created_at_for_humans', 'updated_at_for_humans'];
    public function getChatNameAttribute(){
        return $this->has_email==1 ? $this->client_email : '...';
    }
    public function getCreatedAtStringAttribute(){
        return Carbon::parse($this->created_at)->format('d/m/Y h:i A');
    }
    public function getUpdatedAtStringAttribute(){
        return Carbon::parse($this->updated_at)->format('d/m/Y h:i A');
    }
    public function getCreatedAtForHumansAttribute(){
        return Carbon::parse($this->created_at)->diffForHumans();
    }
    public function getUpdatedAtForHumansAttribute(){
        return Carbon::parse($this->updated_at)->diffForHumans();
    }
    //get chat last message
    public function last_message(){
        return $this->hasOne('App\Models\client_chat_message', 'client_chat_id', 'id')->orderBy('created_at', 'desc');
    }
    //get chat messages
    public function messages(){
        return $this->hasMany('App\Models\client_chat_message', 'client_chat_id', 'id')->orderBy('created_at', 'asc');
    }
    //get chat messages count
    public function messages_count(){
        return $this->hasOne('App\Models\client_chat_message', 'client_chat_id', 'id')->selectRaw('client_chat_id, count(id) as counter')->groupBy('client_chat_id');
    }
}
