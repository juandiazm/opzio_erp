<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_development_event;
use App\Models\ai_development_execution;
use RuntimeException;

class ai_development_state_machine
{
    private const TRANSITIONS = [
        'candidate' => ['awaiting_approval', 'blocked'],
        'awaiting_approval' => ['approved', 'rejected', 'blocked'],
        'approved' => ['preparing', 'blocked'],
        'preparing' => ['analyzing', 'blocked', 'failed'],
        'analyzing' => ['planning', 'developing', 'testing', 'blocked', 'failed'],
        'planning' => ['developing', 'blocked', 'failed'],
        'developing' => ['testing', 'verifying_integrity', 'fixing', 'blocked', 'failed'],
        'testing' => ['integrating_qa', 'fixing', 'blocked', 'failed'],
        'verifying_integrity' => ['integrating_qa', 'blocked', 'failed'],
        'fixing' => ['testing', 'integrating_qa', 'blocked', 'failed'],
        'integrating_qa' => ['waiting_qa_pipeline', 'blocked', 'failed'],
        'waiting_qa_pipeline' => ['waiting_quality_review', 'developing', 'blocked', 'failed'],
        'waiting_quality_review' => ['quality_feedback', 'integrating_main', 'blocked'],
        'quality_feedback' => ['preparing', 'developing', 'blocked', 'failed'],
        'integrating_main' => ['waiting_main_pipeline', 'blocked', 'failed'],
        'waiting_main_pipeline' => ['completed', 'integrating_main', 'developing', 'blocked', 'failed'],
        'failed' => ['preparing', 'waiting_qa_pipeline', 'blocked'],
        'rejected' => [],
        'completed' => [],
        'blocked' => ['awaiting_approval', 'approved', 'preparing', 'developing', 'integrating_qa', 'integrating_main'],
    ];

    public function transition(ai_development_execution $execution, string $nextState, array $metadata = []): ai_development_execution
    {
        $currentState = (string) $execution->status;
        if ($currentState !== $nextState && ! in_array($nextState, self::TRANSITIONS[$currentState] ?? [], true)) {
            throw new RuntimeException("Transicion no permitida: {$currentState} -> {$nextState}.");
        }

        $execution->forceFill([
            'status' => $nextState,
            'current_phase' => $nextState,
            'last_activity_at' => now(),
        ])->save();

        $this->event($execution, $this->eventName($nextState), $metadata);

        return $execution->fresh();
    }

    public function event(ai_development_execution $execution, string $event, array $metadata = [], ?string $phase = null): ai_development_event
    {
        return ai_development_event::create([
            'execution_id' => $execution->id,
            'approval_id' => $execution->approval_id,
            'event' => $event,
            'phase' => $phase ?: $execution->current_phase,
            'attempt' => $execution->attempt,
            'metadata' => $this->safeMetadata($metadata),
        ]);
    }

    public function block(ai_development_execution $execution, string $reason, array $metadata = []): ai_development_execution
    {
        $execution->forceFill([
            'status' => 'blocked',
            'current_phase' => 'blocked',
            'blocked_reason' => mb_substr(trim($reason), 0, 4000),
            'last_activity_at' => now(),
        ])->save();
        $this->event($execution, 'blocked', array_merge($metadata, ['reason' => mb_substr(trim($reason), 0, 1000)]), 'blocked');

        return $execution->fresh();
    }

    private function eventName(string $state): string
    {
        return match ($state) {
            'approved' => 'approved',
            'awaiting_approval' => 'approval_sent',
            'preparing' => 'agent_started',
            'analyzing' => 'analysis_started',
            'planning' => 'plan_created',
            'developing' => 'code_changed',
            'testing' => 'tests_started',
            'fixing' => 'fixing_started',
            'integrating_qa' => 'qa_merge_started',
            'waiting_qa_pipeline' => 'qa_pipeline_started',
            'waiting_quality_review' => 'qa_pipeline_passed',
            'quality_feedback' => 'quality_feedback_detected',
            'integrating_main' => 'main_merge_started',
            'waiting_main_pipeline' => 'main_pipeline_started',
            'completed' => 'completed',
            'rejected' => 'rejected',
            default => 'state_changed',
        };
    }

    private function safeMetadata(array $metadata): array
    {
        unset($metadata['token'], $metadata['api_token'], $metadata['credentials'], $metadata['secret'], $metadata['authorization']);

        $encoded = json_encode($metadata, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        $normalized = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($normalized) ? $normalized : [];
    }
}