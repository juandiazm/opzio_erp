<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class client extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['photo_path', 'complete_name', 'identification_type_string','active_string', 'verified_string', 'created_at_string', 'created_date_string'];
    public function getPhotoPathAttribute(){
        return ($this->photo==null?'images/no-image.jpg':('images/erp/clients/'.$this->photo));
    }
    public function getCompleteNameAttribute(){
        return $this->name.(($this->last_name!=null)?' '.$this->last_name:'');
    }
    public function getIdentificationTypeStringAttribute(){
        switch ($this->identification_type) {
            case '0':
                return 'Nit';
                break;
            case '1':
                return 'Cédula';
                break;
            case '2':
                return 'Pasaporte';
                break;
            case '3':
                return 'Cédula extranjera';
                break;
            default:
                return '';
                break;
        }
    }
    public function getActiveStringAttribute(){
        return ($this->active==1?'Activo':'Inactivo');
    }
    public function getVerifiedStringAttribute(){
        return ($this->verified==1?'Verificado':'No verificado');
    }
    public function getCreatedAtStringAttribute(){
        return date('d/m/Y H:i:s', strtotime($this->created_at));
    }
    public function getCreatedDateStringAttribute(){
        return Carbon::parse($this->created_at)->format('Y-m-d');
    }
    /*relationships*/
    public function licenses(){
        return $this->hasMany(license::class, 'client_id');
    }
    public function incomes(){
        return $this->hasMany(income::class, 'client_id');
    }
    public function country(){
        return $this->belongsTo(country::class, 'country_id');
    }
    public function sector(){
        return $this->belongsTo(sector::class, 'sector_id');
    }
}
