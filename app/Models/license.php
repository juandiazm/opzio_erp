<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class license extends Model
{
    use HasFactory, SoftDeletes;
    protected $appends = ['locked', 'server_date', 'value_string', 'type_string', 'active_string'];
    public function getLockedAttribute(){
        $locked = $this->active==1?false:true;
        if($locked == false){
            //check remaining_days
            $remaining_days = $this->remaining_days+$this->days_to_expire;
            if($remaining_days <= 0){
                $locked = true;
            }
        }
        return $locked;
    }
    public function getServerDateAttribute(){
        return date('Y-m-d');
    }
    public function getValueStringAttribute(){
        return number_format($this->value, 0,',','.');
    }
    public function getTypeStringAttribute(){
        switch ($this->type) {
            case '1':
                return 'Recurrente';
                break;
            case '2':
                return 'Estática';
                break;
        }
    }
    public function getActiveStringAttribute(){
        return $this->active==1?'Activa':'Inactiva';
    }
    public function employee(){
        return $this->belongsTo(employee::class, 'employee_id');
    }
    public function client(){
        return $this->belongsTo(client::class, 'client_id');
    }
    public function service(){
        return $this->belongsTo(service::class, 'service_id');
    }
    public function income_licenses(){
        return $this->hasMany(income_license::class, 'license_id');
    }
    public function license_notifications(){
        return $this->hasMany(license_notification::class, 'license_id');
    }
}
