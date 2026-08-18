<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class income_goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'target_amount',
        'frequency_months',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'frequency_months' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    protected $appends = ['created_at_string'];

    public function getCreatedAtStringAttribute()
    {
        return $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i') : '';
    }
}
