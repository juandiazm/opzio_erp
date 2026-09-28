<?php

namespace App\Jobs;

use App\Models\ai_development_execution;
use App\Services\AiDevelopment\ai_development_notification_service;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\ai_development_states;
use App\Services\AiDevelopment\github_workflow_service;
use App\Services\AiDevelopment\local_git_service;
use App\Services\Jira\jira_client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class monitor_ai_development_pipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $executionId, public string $environment = 'qa')
    {
        $this->onConnection(config('ai_development.queue_connection', 'database'))
            ->onQueue(config('ai_development.queue_name', 'ai-development'));
    }

    public function handle(
        github_workflow_service $workflows,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
        local_git_service $git,
    ): void {
        $execution = ai_development_execution::query()->with(['project.jiraProject', 'project.githubConnection', 'issue.connection', 'issue.reporter'])->find($this->executionId);
        if (! $execution || ! $execution->project?->githubConnection) {
            return;
        }
        $expectedState = $this->environment === 'qa' ? ai_development_states::WAITING_QA_PIPELINE : ai_development_states::WAITING_MAIN_PIPELINE;
        if ($execution->status !== $expectedState) {
            return;
        }

        try {
            $run = $workflows->latestForBranch($execution->project, $execution->environmentBranch($this->environment));
            if (! $run || blank($run['id'])) {
                $this->reschedule();
                return;
            }
            $details = $workflows->details($execution->project, $run['id']);
            if (in_array($details['status'], ['queued', 'in_progress', 'waiting', 'requested'], true)) {
                $this->reschedule();
                return;
            }
            $success = ($details['conclusion'] ?? null) === 'success';
            if (! $success) {
                $this->pipelineFailed($execution, $details, $workflows, $states, $notifications);
                return;
            }
            if ($this->environment === 'qa') {
                $this->qaSucceeded($execution, $details, $states);
                return;
            }
            if (data_get($execution->context, 'promotion_stage') === 'qa_sync_pipeline') {
                $execution->update(['context' => array_merge((array) $execution->context, ['promotion_stage' => 'main_release'])]);
                $states->transition($execution, ai_development_states::INTEGRATING_MAIN, ['stage' => 'qa_sync_passed']);
                promote_ai_development_execution::dispatch($execution->id);
                return;
            }
            $this->mainSucceeded($execution, $details, $states, $git);
        } catch (Throwable $exception) {
            $states->block($execution, 'No fue posible supervisar GitHub Actions: '.mb_substr($exception->getMessage(), 0, 1000));
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
        }
    }

    private function qaSucceeded(ai_development_execution $execution, array $details, ai_development_state_machine $states): void
    {
        try {
            (new jira_client($execution->issue->connection))->transitionIssue($execution->jira_key, 'QA');
            $reporter = $execution->issue->reporter?->display_name ?: 'reporter';
            (new jira_client($execution->issue->connection))->addComment(
                $execution->jira_key,
                '@'.$reporter."\nLa implementacion de esta historia ya esta disponible en QA y se encuentra lista para revision.",
                $execution->issue->reporter?->account_id,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('QA paso en GitHub, pero no fue posible actualizar Jira: '.$exception->getMessage(), 0, $exception);
        }
        $execution->update(['qa_delivered_at' => now(), 'qa_workflow_run_id' => $details['id'], 'last_activity_at' => now()]);
        $states->transition($execution, ai_development_states::WAITING_QUALITY_REVIEW, ['workflow' => $details]);
    }

    private function mainSucceeded(ai_development_execution $execution, array $details, ai_development_state_machine $states, local_git_service $git): void
    {
        $execution->update(['main_workflow_run_id' => $details['id'], 'finished_at' => now(), 'last_activity_at' => now()]);
        $states->transition($execution, ai_development_states::COMPLETED, ['workflow' => $details]);
        if ($execution->project?->githubConnection && filled($execution->feature_branch)) {
            try {
                (new \App\Services\AiDevelopment\github_client($execution->project->githubConnection))->deleteBranch($execution->project->github_owner, $execution->project->github_repository, $execution->feature_branch);
            } catch (Throwable $exception) {
                $states->event($execution, 'feature_branch_cleanup_failed', ['message' => mb_substr($exception->getMessage(), 0, 500)]);
            }
        }
        try {
            $git->deleteWorkspace($execution);
        } catch (Throwable $exception) {
            $states->event($execution, 'workspace_cleanup_failed', ['message' => mb_substr($exception->getMessage(), 0, 500)]);
        }
    }

    private function pipelineFailed(
        ai_development_execution $execution,
        array $details,
        github_workflow_service $workflows,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void {
        $logs = '';
        try {
            $logs = $workflows->logs($execution->project, (string) $details['id']);
        } catch (Throwable $exception) {
            $logs = 'No fue posible recuperar los logs: '.$exception->getMessage();
        }
        $attempts = $this->environment === 'qa' ? (int) $execution->ci_attempts + 1 : (int) $execution->main_ci_attempts + 1;
        $execution->increment($this->environment === 'qa' ? 'ci_attempts' : 'main_ci_attempts');
        $states->event($execution->fresh(), $this->environment.'_pipeline_failed', [
            'workflow' => $details,
            'attempt' => $attempts,
            'logs' => mb_substr($logs, 0, (int) config('ai_development.pipeline.max_log_bytes', 12000)),
        ]);
        if ($attempts >= max(1, (int) $execution->project->max_ci_attempts)) {
            $states->block($execution->fresh(), 'Se alcanzo el maximo de fallos CI/CD de '.$this->environment.'.');
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
            return;
        }
        $execution = $execution->fresh();
        $execution->update(['context' => array_merge((array) $execution->context, ['pipeline_feedback' => mb_substr($logs, 0, 12000)])]);
        if ($execution->status !== ai_development_states::FAILED) {
            $states->transition($execution, ai_development_states::FAILED, ['pipeline' => $this->environment]);
        }
        run_ai_development_execution::dispatch($execution->id);
    }

    private function reschedule(): void
    {
        self::dispatch($this->executionId, $this->environment)->delay(now()->addSeconds(max(5, (int) config('ai_development.pipeline.poll_delay_seconds', 60))));
    }
}