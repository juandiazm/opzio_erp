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
        'recurrence_enabled' => 'boolean',
        'recurrence_interval' => 'integer',
        'recurrence_next_at' => 'datetime',
        'recurrence_ends_at' => 'datetime',
        'recurrence_send_automatically' => 'boolean',
        'recurrence_last_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'signature_token' => 'encrypted',
        'signature_uploaded_at' => 'datetime',
        'signature_accepted_at' => 'datetime',
        'generation_data' => 'array',
        'sources' => 'array',
    ];

    protected $appends = [
        'status_string',
        'send_status_string',
        'signature_status_string',
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

    public function license()
    {
        return $this->belongsTo(license::class, 'license_id');
    }

    public function recurrenceParent()
    {
        return $this->belongsTo(self::class, 'recurrence_parent_id');
    }

    public function recurrenceChildren()
    {
        return $this->hasMany(self::class, 'recurrence_parent_id');
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
            'pending_signature' => 'En espera de firma',
            'signed' => 'Firmado',
            'expired' => 'Vencido',
            'completed' => 'Finalizado',
            'cancelled' => 'Cancelado',
        ][$this->status] ?? $this->status;
    }

    public function getSendStatusStringAttribute()
    {
        return [
            'not_sent' => 'No enviado',
            'sent' => 'Enviado',
            'failed' => 'Fallido',
        ][$this->send_status] ?? ($this->send_status ?: 'No enviado');
    }

    public function getSignatureStatusStringAttribute()
    {
        return [
            'pending' => 'Pendiente',
            'uploaded' => 'Cargado',
            'accepted' => 'Aceptado',
        ][$this->signature_status] ?? ($this->signature_status ?: 'Pendiente');
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