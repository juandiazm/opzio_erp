<?php

namespace App\Jobs;

use App\Models\ai_development_execution;
use App\Services\AiDevelopment\ai_development_notification_service;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\ai_development_states;
use App\Services\AiDevelopment\github_workflow_service;
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

    public function __construct(
        public int $executionId,
        public string $environment = 'qa',
        public ?string $workflowRunId = null,
        public bool $manualRetry = false,
    )
    {
        $this->onConnection(config('ai_development.queue_connection', 'database'))
            ->onQueue(config('ai_development.queue_name', 'ai-development'));
    }

    public function handle(
        github_workflow_service $workflows,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
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
            $workflowRunId = $this->workflowRunId;
            if (blank($workflowRunId)) {
                $run = $workflows->latestForBranch($execution->project, $execution->environmentBranch($this->environment));
                if (! $run || blank($run['id'])) {
                    if ($this->manualRetry) {
                        $reason = 'No se encontro un workflow reciente en GitHub Actions. Cuando relances el despliegue, solicita otra revalidacion.';
                        $this->waitForManualRetry($execution, [], $reason, $states);

                        return;
                    }
                    $this->reschedule();
                    return;
                }
                $workflowRunId = (string) $run['id'];
            }
            $details = $workflows->details($execution->project, $workflowRunId);
            $workflowRunId = (string) ($details['id'] ?? $workflowRunId);
            $workflowColumn = $this->environment === 'qa' ? 'qa_workflow_run_id' : 'main_workflow_run_id';
            $execution->update([$workflowColumn => $workflowRunId]);
            if (in_array($details['status'], ['queued', 'in_progress', 'waiting', 'requested'], true)) {
                $this->reschedule($workflowRunId);
                return;
            }
            $success = ($details['conclusion'] ?? null) === 'success';
            if (! $success) {
                $this->pipelineFailed($execution, $details, $workflows, $states, $notifications, $this->manualRetry);
                return;
            }
            if ($this->environment === 'qa') {
                $this->qaSucceeded($execution, $details, $states, $notifications);
                return;
            }
            if (data_get($execution->context, 'promotion_stage') === 'qa_sync_pipeline') {
                $execution->update(['context' => array_merge((array) $execution->context, ['promotion_stage' => 'main_release'])]);
                $states->transition($execution, ai_development_states::INTEGRATING_MAIN, ['stage' => 'qa_sync_passed']);
                promote_ai_development_execution::dispatch($execution->id);
                return;
            }
            $this->mainSucceeded($execution, $details, $states, $notifications);
        } catch (Throwable $exception) {
            $reason = 'No fue posible supervisar GitHub Actions: '.$this->safeText($exception->getMessage(), 1000);
            if ($this->manualRetry) {
                $this->waitForManualRetry($execution->fresh(), ['id' => $this->workflowRunId], $reason, $states);

                return;
            }
            $states->block($execution, $reason);
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
        }
    }

    private function qaSucceeded(
        ai_development_execution $execution,
        array $details,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void
    {
        try {
            (new jira_client($execution->issue->connection))->transitionIssue($execution->jira_key, \App\Services\AiDevelopment\jira_automation_statuses::QUALITY);
            $reporter = $execution->issue->reporter?->display_name ?: 'reporter';
            (new jira_client($execution->issue->connection))->addComment(
                $execution->jira_key,
                '@'.$reporter."\nLa implementacion de esta historia ya esta disponible en QA y se encuentra lista para revision.",
                $execution->issue->reporter?->account_id,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('QA paso en GitHub, pero no fue posible actualizar Jira: '.$exception->getMessage(), 0, $exception);
        }
        $execution->update([
            'qa_delivered_at' => now(),
            'qa_workflow_run_id' => $details['id'],
            'consecutive_failures' => 0,
            'error' => null,
            'blocked_reason' => null,
            'last_activity_at' => now(),
        ]);
        $execution = $states->transition($execution, ai_development_states::WAITING_QUALITY_REVIEW, ['workflow' => $details]);
        $notification = $notifications->qaAvailable($execution);
        $states->event($execution, ($notification['status'] ?? 0) === 1 ? 'qa_reporter_notified' : 'qa_reporter_notification_failed', [
            'message' => mb_substr((string) ($notification['message'] ?? ''), 0, 500),
        ]);
    }

    private function mainSucceeded(
        ai_development_execution $execution,
        array $details,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void
    {
        $execution->update([
            'main_workflow_run_id' => $details['id'],
            'finished_at' => now(),
            'last_activity_at' => now(),
            'error' => null,
            'blocked_reason' => null,
        ]);
        $execution = $states->transition($execution, ai_development_states::COMPLETED, ['workflow' => $details]);
        $reporterNotification = $notifications->reporterFlow(
            $execution,
            'Historia completada',
            'La implementacion supero el pipeline principal y el flujo autonomo finalizo correctamente.',
            ['Pipeline main' => $details['id'] ?? '-'],
        );
        $states->event($execution, ($reporterNotification['status'] ?? 0) === 1 ? 'reporter_notification_sent' : 'reporter_notification_failed', [
            'title' => 'Historia completada',
            'message' => mb_substr((string) ($reporterNotification['message'] ?? ''), 0, 500),
        ]);
        $completionNotification = $notifications->completed($execution->fresh(['project.jiraProject', 'issue', 'agent']), $details);
        $states->event($execution->fresh(), ($completionNotification['status'] ?? 0) === 1 ? 'completion_notification_sent' : 'completion_notification_failed', [
            'message' => mb_substr((string) ($completionNotification['message'] ?? ''), 0, 500),
        ]);
        if ($execution->project?->githubConnection && filled($execution->feature_branch)) {
            try {
                (new \App\Services\AiDevelopment\github_client($execution->project->githubConnection))->deleteBranch($execution->project->github_owner, $execution->project->github_repository, $execution->feature_branch);
            } catch (Throwable $exception) {
                $states->event($execution, 'feature_branch_cleanup_failed', ['message' => mb_substr($exception->getMessage(), 0, 500)]);
            }
        }
    }

    private function pipelineFailed(
        ai_development_execution $execution,
        array $details,
        github_workflow_service $workflows,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
        bool $manualRetry,
    ): void {
        if ($manualRetry) {
            $reason = 'El workflow de GitHub Actions aun no aparece como exitoso. Si ya corregiste el despliegue, vuelve a solicitar la revalidacion.';
            $this->waitForManualRetry($execution, $details, $reason, $states);

            return;
        }

        $logs = '';
        try {
            $logs = $workflows->logs($execution->project, (string) $details['id']);
        } catch (Throwable $exception) {
            $logs = 'No fue posible recuperar los logs: '.$exception->getMessage();
        }
        $compressedLogs = $this->isCompressedLogs($logs);
        $logs = $compressedLogs
            ? 'GitHub Actions entrego los logs en un archivo ZIP; consulta el workflow en GitHub.'
            : $this->safeText($logs, (int) config('ai_development.pipeline.max_log_bytes', 12000));
        $attempts = $this->environment === 'qa' ? (int) $execution->ci_attempts + 1 : (int) $execution->main_ci_attempts + 1;
        $execution->increment($this->environment === 'qa' ? 'ci_attempts' : 'main_ci_attempts');
        $states->event($execution->fresh(), $this->environment.'_pipeline_failed', [
            'workflow' => $details,
            'attempt' => $attempts,
            'logs' => mb_substr($logs, 0, (int) config('ai_development.pipeline.max_log_bytes', 12000)),
        ]);
        if ($compressedLogs) {
            $this->waitForManualRetry(
                $execution->fresh(),
                $details,
                'El pipeline de GitHub Actions fallo y sus logs llegaron comprimidos. Revisa el workflow y solicita una nueva validacion cuando este corregido.',
                $states,
            );

            return;
        }
        if ($this->isExternalFailure($logs)) {
            $this->waitForManualRetry(
                $execution->fresh(),
                $details,
                'Fallo externo de infraestructura en CI/CD '.$this->environment.': '.mb_substr($logs, 0, 1200),
                $states,
            );

            return;
        }
        if ($attempts >= max(1, (int) $execution->project->max_ci_attempts)) {
            $this->waitForManualRetry(
                $execution->fresh(),
                $details,
                'Se alcanzo el maximo de fallos CI/CD de '.$this->environment.'. Revisa el workflow y solicita una nueva validacion cuando este corregido.',
                $states,
            );

            return;
        }
        $execution = $execution->fresh();
        $execution->update(['context' => array_merge((array) $execution->context, ['pipeline_feedback' => mb_substr($logs, 0, 12000)])]);
        if ($execution->status !== ai_development_states::FAILED) {
            $states->transition($execution, ai_development_states::FAILED, ['pipeline' => $this->environment]);
        }
        run_ai_development_execution::dispatch($execution->id);
    }

    private function waitForManualRetry(
        ai_development_execution $execution,
        array $details,
        string $reason,
        ai_development_state_machine $states,
    ): void {
        $manualState = $this->environment === 'qa'
            ? ai_development_states::WAITING_MANUAL_QA_PIPELINE
            : ai_development_states::WAITING_MANUAL_MAIN_PIPELINE;
        $execution->forceFill([
            'error' => $this->safeText($reason, 4000),
            'blocked_reason' => null,
            'last_activity_at' => now(),
        ])->save();
        $states->transition($execution, $manualState, [
            'workflow' => $details,
            'manual_recheck' => $this->manualRetry,
            'reason' => $this->safeText($reason, 1000),
        ]);
    }

    private function reschedule(?string $workflowRunId = null): void
    {
        self::dispatch(
            $this->executionId,
            $this->environment,
            $workflowRunId ?: $this->workflowRunId,
            $this->manualRetry,
        )->delay(now()->addSeconds(max(5, (int) config('ai_development.pipeline.poll_delay_seconds', 60))));
    }

    private function isCompressedLogs(string $logs): bool
    {
        return str_starts_with($logs, "PK\x03\x04")
            || str_starts_with($logs, "PK\x05\x06")
            || str_starts_with($logs, "PK\x07\x08");
    }

    private function safeText(string $text, int $limit): string
    {
        $encoded = json_encode($text, JSON_INVALID_UTF8_SUBSTITUTE);
        $normalized = is_string($encoded) ? json_decode($encoded, true) : null;

        return mb_substr(is_string($normalized) ? $normalized : '', 0, max(0, $limit));
    }

    private function isExternalFailure(string $logs): bool
    {
        $normalized = strtolower($logs);
        foreach ([
            'scp: stat',
            'no such file or directory',
            'permission denied',
            'authentication failed',
            'could not resolve host',
            'runner offline',
            'rate limit',
            'service unavailable',
            'connection timed out',
        ] as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }
}