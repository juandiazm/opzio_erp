<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $executions = DB::table('ai_development_executions')
            ->where('status', 'blocked')
            ->where(function ($query): void {
                $query->where('blocked_reason', 'like', '%No fue posible supervisar GitHub Actions:%')
                    ->orWhere('blocked_reason', 'like', '%Se alcanzo el maximo de fallos CI/CD%')
                    ->orWhere('blocked_reason', 'like', '%Fallo externo de infraestructura en CI/CD%');
            })
            ->get(['id', 'approval_id', 'attempt', 'blocked_reason']);

        foreach ($executions as $execution) {
            $lastPipelineEvent = DB::table('ai_development_events')
                ->where('execution_id', $execution->id)
                ->whereIn('event', [
                    'qa_pipeline_started',
                    'qa_pipeline_failed',
                    'qa_pipeline_passed',
                    'main_pipeline_started',
                    'main_pipeline_failed',
                    'main_pipeline_passed',
                ])
                ->latest('id')
                ->value('event');

            if (! in_array($lastPipelineEvent, ['qa_pipeline_failed', 'main_pipeline_failed'], true)) {
                continue;
            }

            $environment = str_starts_with($lastPipelineEvent, 'qa_') ? 'qa' : 'main';
            $manualState = $environment === 'qa'
                ? 'waiting_manual_qa_pipeline'
                : 'waiting_manual_main_pipeline';
            $reason = mb_substr((string) $execution->blocked_reason, 0, 4000);
            $now = now();

            DB::transaction(function () use ($execution, $environment, $manualState, $reason, $lastPipelineEvent, $now): void {
                DB::table('ai_development_executions')
                    ->where('id', $execution->id)
                    ->update([
                        'status' => $manualState,
                        'current_phase' => $manualState,
                        'error' => $reason,
                        'blocked_reason' => null,
                        'last_activity_at' => $now,
                        'updated_at' => $now,
                    ]);

                DB::table('ai_development_events')->insert([
                    'execution_id' => $execution->id,
                    'approval_id' => $execution->approval_id,
                    'event' => $environment.'_pipeline_manual_retry_required',
                    'phase' => $manualState,
                    'actor_type' => 'migration',
                    'actor_id' => null,
                    'attempt' => $execution->attempt,
                    'metadata' => json_encode([
                        'environment' => $environment,
                        'reason' => $reason,
                        'migrated_from' => 'blocked',
                        'last_pipeline_event' => $lastPipelineEvent,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
        }
    }

    public function down(): void
    {
    }
};