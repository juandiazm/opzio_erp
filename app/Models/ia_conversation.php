<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ia_conversation extends Model
{
    protected $table = 'ia_conversations';

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'openai_last_response_id',
        'report_period',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(user::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(client::class, 'client_id');
    }

    public function turns()
    {
        return $this->hasMany(ia_turn::class, 'conversation_id')->orderBy('turn_number');
    }

    public function latest_turn()
    {
        return $this->hasOne(ia_turn::class, 'conversation_id')->latestOfMany('turn_number');
    }
}
