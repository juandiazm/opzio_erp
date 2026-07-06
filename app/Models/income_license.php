<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class income_license extends Model
{
    use HasFactory;
    protected $appends = ['total_string', 'comission_value'];
    public function income(){
        return $this->belongsTo(income::class, 'income_id');
    }
    public function license(){
        return $this->belongsTo(license::class, 'license_id');
    }
    public function employee(){
        return $this->belongsTo(employee::class, 'employee_id');
    }
    public function getComissionValueAttribute(){
        return $this->comission==null?0:$this->comission*$this->value/100;
    }
    public function getTotalStringAttribute(){
        return number_format($this->total, 0,',','.');
    }
}
