<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use \Carbon\Carbon;

class income extends Model
{
    use HasFactory, SoftDeletes;
    protected $appends = ['state_text', 'created_at_string', 'payment_link', 'total_string', 'doc_url', 'bill_final_value_string','payment_state_text', 'cutoff_date_string', 'days_overdue', 'total_advances', 'balance_pending'];
    public function getStateTextAttribute(){
        switch ($this->state) {
            case '0':
                return 'Cotización';
                break;
            case '1':
                return 'Rechazada';
                break;
            case '2':
                return 'Aprobada';
                break;
            case '3':
                return 'Pagada';
                break;
            case '4':
                return 'Facturada';
                break;
            default:
                return '';
                break;
        }
    }
    public function getCreatedAtStringAttribute(){
        return Carbon::parse($this->created_at)->format('Y-m-d H:i');
    }
    public function getPaymentLinkAttribute(){
        return URL::to('/client/payments/pay/'.$this->unique_id.'?external=true');
    }
    public function getTotalStringAttribute(){
        return number_format($this->total, 0,',','.');
    }
    public function getDocUrlAttribute(){
        return Storage::disk('incomes_pdfs')->url($this->unique_id.'.pdf');
    }
    public function getBillFinalValueStringAttribute(){
        return $this->bill_final_value==null?number_format($this->total, 0,',','.'):number_format($this->bill_final_value, 0,',','.');
    }
    public function getPaymentStateTextAttribute(){
        return $this->payment_state==0?'Pendiente':'Pagado';
    }
    public function getCutoffDateStringAttribute(){
        return $this->cutoff_date ? Carbon::parse($this->cutoff_date)->format('Y-m-d') : '';
    }
    public function getDaysOverdueAttribute(){
        if (!$this->cutoff_date) {
            return 0;
        }
        $cutoffDate = Carbon::parse($this->cutoff_date);
        $today = Carbon::now();
        
        // Si la fecha de corte ya pasó, calcular los días vencidos
        if ($today->greaterThan($cutoffDate)) {
            return $today->diffInDays($cutoffDate);
        }
        
        return 0;
    }
    /*relationships*/
    public function income_licenses(){
        return $this->hasMany(income_license::class, 'income_id');
    }
    public function client(){
        return $this->belongsTo(client::class, 'client_id');
    }
    public function income_advances(){
        return $this->hasMany(income_advance::class, 'income_id');
    }
    
    // Calcular total de abonos
    public function getTotalAdvancesAttribute(){
        return $this->income_advances()->sum('amount');
    }
    
    // Calcular balance pendiente
    public function getBalancePendingAttribute(){
        return $this->total - $this->total_advances;
    }
}
