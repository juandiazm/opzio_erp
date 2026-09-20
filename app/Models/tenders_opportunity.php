<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tenders_opportunity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'published_at' => 'datetime',
        'last_published_at' => 'datetime',
        'deadline_at' => 'datetime',
        'raw_payload' => 'array',
        'embedding' => 'array',
    ];

    public function versions()
    {
        return $this->hasMany(tenders_opportunity_version::class, 'tenders_opportunity_id');
    }

    public function documents()
    {
        return $this->hasMany(tenders_document::class, 'tenders_opportunity_id');
    }

    public function feedbackEvents()
    {
        return $this->hasMany(tenders_feedback_event::class, 'tenders_opportunity_id');
    }

    public function pipelineItems()
    {
        return $this->hasMany(tenders_pipeline_item::class, 'tenders_opportunity_id');
    }

    public function pipelineEntries()
    {
        return $this->hasMany(tenders_pipeline_entry::class, 'tenders_opportunity_id');
    }
}
