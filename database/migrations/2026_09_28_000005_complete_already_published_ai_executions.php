<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $executions = DB::table('ai_development_executions')
            ->where('status', 'blocked')
            ->where('blocked_reason', 'like', '%No commits between qa and main%')
            ->get([
                'id',
                'approval_id',
                'attempt',
                'context',
            ]);

        foreach ($executions as $execution) {
            $context = json_decode((string) $execution->context, true);
            $context = is_array($context) ? $context : [];
            $context = array_merge($context, [
                'promotion_stage' => 'main_release_completed',
                'main_promotion_status' => 'already_published',
                'main_promotion_resolution' => 'migration_no_commits_between_qa_and_main',
            ]);

            DB::table('ai_development_executions')
                ->where('id', $execution->id)
                ->update([
                    'status' => 'completed',
                    'current_phase' => 'completed',
                    'finished_at' => $now,
                    'last_activity_at' => $now,
                    'blocked_reason' => null,
                    'error' => null,
                    'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => $now,
                ]);

            DB::table('ai_development_events')->insert([
                'execution_id' => $execution->id,
                'approval_id' => $execution->approval_id,
                'event' => 'completed',
                'phase' => 'completed',
                'actor_type' => 'migration',
                'actor_id' => null,
                'attempt' => $execution->attempt,
                'metadata' => json_encode([
                    'promotion_status' => 'already_published',
                    'reason' => 'no_commits_between_qa_and_main',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
    }
};