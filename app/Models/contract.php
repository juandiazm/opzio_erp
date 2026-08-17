<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'generation_data' => 'array',
    ];

    protected $appends = [
        'status_string',
        'contractable_name',
        'contractable_email',
    ];

    public function type()
    {
        return $this->belongsTo(contract_type::class, 'contract_type_id');
    }

    public function template()
    {
        return $this->belongsTo(contract_template::class, 'contract_template_id');
    }

    public function schedule()
    {
        return $this->belongsTo(contract_schedule::class, 'schedule_id');
    }

    public function contractable()
    {
        return $this->morphTo();
    }

    public function getStatusStringAttribute()
    {
        return [
            'draft' => 'Borrador',
            'generated' => 'Generado',
            'sent' => 'Enviado',
            'signed' => 'Firmado',
            'expired' => 'Vencido',
            'cancelled' => 'Cancelado',
        ][$this->status] ?? $this->status;
    }

    public function getContractableNameAttribute()
    {
        $contractable = $this->contractable;
        if (!$contractable) {
            return '';
        }

        $name = trim((string) ($contractable->name ?? ''));
        $lastName = trim((string) ($contractable->lastname ?? $contractable->last_name ?? ''));
        return trim($name.' '.$lastName) ?: trim((string) ($contractable->complete_name ?? ''));
    }

    public function getContractableEmailAttribute()
    {
        $contractable = $this->contractable;
        if (!$contractable) {
            return '';
        }

        return trim((string) ($contractable->email ?: $contractable->work_email ?: $contractable->personal_email ?: ''));
    }
}