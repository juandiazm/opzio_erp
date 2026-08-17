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

    public function provider(){
        return $this->belongsTo(provider::class, 'provider_id');
    }

    public function employee(){
        return $this->belongsTo(employee::class, 'employee_id');
    }

    public function department(){
        return $this->belongsTo(department::class, 'department_id');
    }

    public function user(){
        return $this->belongsTo(user::class, 'user_id');
    }

    public function client(){
        return $this->belongsTo(client::class, 'client_id');
    }
}
