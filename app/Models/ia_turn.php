<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ia_turn extends Model
{
    protected $table = 'ia_turns';

    protected $fillable = [
        'conversation_id',
        'openai_response_id',
        'parent_response_id',
        'user_input',
        'report_json',
        'turn_number',
        'input_tokens',
        'output_tokens',
    ];

    protected $casts = [
        'report_json' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(ia_conversation::class, 'conversation_id');
    }
}
