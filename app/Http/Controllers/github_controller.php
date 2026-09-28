<?php

namespace App\Http\Controllers;

use App\Models\ai_development_event;
use App\Models\ai_development_execution;
use App\Services\AiDevelopment\jira_automation_service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class github_controller extends ai_development_controller
{
    public function page(Request $request): View
    {
        return view('erp.github');
    }

    public function execution_detail(Request $request, int $execution): JsonResponse
    {
        try {
            $record = ai_development_execution::query()
                ->with(['issue.project', 'project.jiraProject', 'agent', 'approval.selectedAgent'])
                ->findOrFail($execution);

            return response()->json([
                'status' => 1,
                'data' => [
                    'execution' => [
                        'id' => $record->id,
                        'jira_key' => $record->jira_key,
                        'summary' => $record->issue?->summary,
                        'project' => $record->project?->jiraProject?->project_key,
                        'repository' => $record->repository,
                        'agent' => $record->agent?->name,
                        'status' => $record->status,
                        'phase' => $record->current_phase,
                        'feature_branch' => $record->feature_branch,
                        'base_branch' => $record->base_branch,
                        'attempt' => $record->attempt,
                        'ci_attempts' => $record->ci_attempts,
                        'main_ci_attempts' => $record->main_ci_attempts,
                        'github_task_id' => $record->github_task_id,
                        'github_task_state' => $record->github_task_state,
                        'github_task_url' => $record->github_task_url,
                        'github_pull_request_number' => $record->github_pull_request_number,
                        'started_at' => $record->started_at?->toIso8601String(),
                        'finished_at' => $record->finished_at?->toIso8601String(),
                        'last_activity_at' => $record->last_activity_at?->toIso8601String(),
                        'error' => $record->error,
                        'blocked_reason' => $record->blocked_reason,
                        'context' => $this->safeContext((array) $record->context),
                    ],
                    'events' => ai_development_event::query()
                        ->where('execution_id', $record->id)
                        ->latest('created_at')
                        ->limit(100)
                        ->get()
                        ->map(fn (ai_development_event $event): array => [
                            'event' => $event->event,
                            'phase' => $event->phase,
                            'attempt' => $event->attempt,
                            'metadata' => $this->safeContext((array) $event->metadata),
                            'created_at' => $event->created_at?->toIso8601String(),
                        ])
                        ->values()
                        ->all(),
                ],
            ]);
        } catch (Throwable $exception) {
            return response()->json(['status' => 0, 'message' => $exception->getMessage()], 422);
        }
    }

    public function restart_execution(Request $request, int $execution, jira_automation_service $service): JsonResponse
    {
        try {
            $record = $service->restartExecution($execution, data_get(session('user'), 'id'));

            return response()->json([
                'status' => 1,
                'message' => 'La ejecucion fue reiniciada y enviada nuevamente al flujo.',
                'data' => ['execution_id' => $record->id, 'status' => $record->status],
            ]);
        } catch (Throwable $exception) {
            return response()->json(['status' => 0, 'message' => $exception->getMessage()], 422);
        }
    }

    private function safeContext(array $context): array
    {
        unset($context['token'], $context['api_token'], $context['credentials'], $context['secret'], $context['authorization']);

        return $context;
    }
}