<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class tenders_connection extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function syncRuns()
    {
        return $this->hasMany(tenders_sync_run::class, 'tenders_connection_id');
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }
}
