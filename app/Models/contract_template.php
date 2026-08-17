<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class contract_template extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'variables' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo(contract_type::class, 'contract_type_id');
    }

    public function contracts()
    {
        return $this->hasMany(contract::class, 'contract_template_id');
    }

    public function schedules()
    {
        return $this->hasMany(contract_schedule::class, 'contract_template_id');
    }
}