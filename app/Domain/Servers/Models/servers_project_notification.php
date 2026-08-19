<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;

class servers_project_notification extends Model
{
    protected $fillable = [
        'project_id',
        'source_type',
        'source_id',
        'source_key',
        'channel',
        'value',
        'recipient_name',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'source_id' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(servers_project::class, 'project_id');
    }
}
