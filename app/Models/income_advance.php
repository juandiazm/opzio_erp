<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class income_advance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'income_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'created_by'
    ];
    
    protected $appends = ['amount_string', 'payment_date_string'];
    
    // Relaciones
    public function income()
    {
        return $this->belongsTo(income::class, 'income_id');
    }
    
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    
    // Atributos
    public function getAmountStringAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }
    
    public function getPaymentDateStringAttribute()
    {
        return $this->payment_date ? Carbon::parse($this->payment_date)->format('Y-m-d') : '';
    }
}
