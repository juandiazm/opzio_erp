<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class employee extends Model
{
    use HasFactory, SoftDeletes;
    protected $appends = ['complete_name', 'id_type_string','state_string','payment_type_string', 'account_type_string'];
    public function getCompleteNameAttribute(){
        return $this->name.(($this->last_name!=null && $this->last_name!='')?' '.$this->last_name:'');
    }
    public function getStateStringAttribute(){
        return $this->state==1?'Activo':'Inactivo';
    }
    public function getIdTypeStringAttribute(){
        return $this->id_type==1?'Cédula de ciudadanía':($this->id_type==2?'Cédula de extranjería':'Pasaporte');
    }
    public function getPaymentTypeStringAttribute(){
        switch ($this->payment_type){
            case 0:
                return 'Quincenal';
                break;
            case 1:
                return 'Mensual';
                break;
            case 2:
                return 'Semanal';
                break;
            case 3:
                return 'Diario';
                break;
        }
    }
    public function getAccountTypeStringAttribute(){
        switch ($this->account_type){
            case 0:
                return 'Ahorros';
                break;
            case 1:
                return 'Corriente';
                break;
        }
    }
    public function country(){
        return $this->belongsTo(country::class, 'country_id');
    }
    public function eps(){
        return $this->belongsTo(eps::class, 'eps_id');
    }
    public function afp(){
        return $this->belongsTo(afp::class, 'afp_id');
    }
    public function arl(){
        return $this->belongsTo(arl::class, 'arl_id');
    }
}
