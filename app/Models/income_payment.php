<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class income_payment extends Model
{
    use HasFactory;
    protected $appends = ['payment_state_string'];
    public function getPaymentStateStringAttribute()
    {
        switch($this->payment_state){
            case 0:
                return 'Pendiente';
                break;
            case 1:
                return 'Aprobado';
                break;
            case 2:
                return 'Rechazado';
                break;
            default:
                return 'Pendiente';
                break;
        }
    }
    public function income()
    {
        return $this->belongsTo(income::class);
    }
}
