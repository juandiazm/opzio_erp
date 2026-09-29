<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ai_agent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'command' => 'encrypted',
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
        'description' => 'string',
        'cost_tier' => 'string',
    ];
}