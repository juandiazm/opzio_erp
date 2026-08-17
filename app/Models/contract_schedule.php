<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class contract_schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'next_run_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_run_at' => 'datetime',
        'send_automatically' => 'boolean',
        'active' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(contract_type::class, 'contract_type_id');
    }

    public function template()
    {
        return $this->belongsTo(contract_template::class, 'contract_template_id');
    }

    public function contracts()
    {
        return $this->hasMany(contract::class, 'schedule_id');
    }

    public function contractable()
    {
        return $this->morphTo();
    }
}