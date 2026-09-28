<?php

namespace App\Jobs;

use App\Models\ai_development_execution;
use App\Services\AiDevelopment\ai_development_notification_service;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\ai_development_states;
use App\Services\AiDevelopment\github_client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class promote_ai_development_execution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $executionId)
    {
        $this->onConnection(config('ai_development.queue_connection', 'database'))
            ->onQueue(config('ai_development.queue_name', 'ai-development'));
    }

    public function handle(ai_development_state_machine $states, ai_development_notification_service $notifications): void
    {
        $execution = ai_development_execution::query()->with(['project.githubConnection', 'project.jiraProject', 'issue.connection'])->find($this->executionId);
        if (! $execution || $execution->status !== ai_development_states::INTEGRATING_MAIN || ! $execution->project?->githubConnection) {
            return;
        }
        try {
            $project = $execution->project;
            $github = new github_client($project->githubConnection);
            $context = (array) $execution->context;
            $merge = $github->mergeBranches(
                $project->github_owner,
                $project->github_repository,
                'main',
                $execution->base_branch,
                $execution->jira_key.' promote '.$execution->base_branch.' to main',
            );
            if (($merge['merged'] ?? false) !== true) {
                throw new RuntimeException('No fue posible integrar QA hacia main.');
            }
            $execution->update(['context' => array_merge($context, [
                'promotion_stage' => 'main_release_pipeline',
                'main_merge_commit' => $merge['sha'] ?? null,
            ])]);
            $execution = $states->transition($execution, ai_development_states::WAITING_MAIN_PIPELINE, [
                'stage' => 'main_release',
                'source_branch' => $execution->base_branch,
                'target_branch' => 'main',
                'merge_commit' => $merge['sha'] ?? null,
            ]);
            $this->notifyReporter($execution, $states, $notifications, 'Publicacion en curso', 'La implementacion de QA se esta integrando en la rama principal.', ['Commit de merge' => $merge['sha'] ?? '-']);
            monitor_ai_development_pipeline::dispatch($execution->id, 'main')->delay(now()->addSeconds(5));
        } catch (Throwable $exception) {
            if ($this->isAlreadyPublished($execution, $exception)) {
                $this->completeAsAlreadyPublished($execution, $states, $notifications, $exception);

                return;
            }
            $states->block($execution, 'No fue posible promover la historia: '.mb_substr($exception->getMessage(), 0, 1200));
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
        }
    }

    private function isAlreadyPublished(ai_development_execution $execution, Throwable $exception): bool
    {
        $message = strtolower((string) preg_replace('/\s+/', ' ', $exception->getMessage()));
        $baseBranch = strtolower(trim((string) ($execution->base_branch ?: 'qa')));

        return str_contains($message, 'validation failed')
            && str_contains($message, 'no commits between '.$baseBranch.' and main');
    }

    private function completeAsAlreadyPublished(
        ai_development_execution $execution,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
        Throwable $exception,
    ): void {
        $resolution = 'no_commits_between_qa_and_main';
        $execution->update([
            'blocked_reason' => null,
            'error' => null,
            'context' => array_merge((array) $execution->context, [
                'promotion_stage' => 'main_release_completed',
                'main_promotion_status' => 'already_published',
                'main_promotion_resolution' => $resolution,
            ]),
        ]);
        $execution = $states->transition($execution, ai_development_states::COMPLETED, [
            'stage' => 'main_release',
            'source_branch' => $execution->base_branch,
            'target_branch' => 'main',
            'promotion_status' => 'already_published',
            'reason' => $resolution,
            'message' => mb_substr($exception->getMessage(), 0, 500),
        ]);
        $execution->update([
            'finished_at' => now(),
            'last_activity_at' => now(),
        ]);
        $this->notifyReporter(
            $execution,
            $states,
            $notifications,
            'Historia completada',
            'Los cambios ya estaban publicados en main; no habia commits nuevos pendientes desde '.$execution->base_branch.'.',
            ['Resolucion' => 'Cambios ya publicados'],
        );
        $completionNotification = $notifications->completed($execution->fresh(['project.jiraProject', 'issue', 'agent']), [
            'status' => 'completed',
            'conclusion' => 'success',
            'promotion_status' => 'already_published',
            'reason' => $resolution,
        ]);
        $states->event($execution->fresh(), ($completionNotification['status'] ?? 0) === 1 ? 'completion_notification_sent' : 'completion_notification_failed', [
            'message' => mb_substr((string) ($completionNotification['message'] ?? ''), 0, 500),
        ]);
    }

    private function notifyReporter(
        ai_development_execution $execution,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
        string $title,
        string $message,
        array $details = [],
    ): void {
        $notification = $notifications->reporterFlow($execution, $title, $message, $details);
        $states->event($execution, ($notification['status'] ?? 0) === 1 ? 'reporter_notification_sent' : 'reporter_notification_failed', [
            'title' => $title,
            'message' => mb_substr((string) ($notification['message'] ?? ''), 0, 500),
        ]);
    }
}