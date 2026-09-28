<?php

namespace App\Jobs;

use App\Models\ai_development_execution;
use App\Services\AiDevelopment\ai_agent_provider_interface;
use App\Services\AiDevelopment\ai_development_notification_service;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\ai_development_states;
use App\Services\AiDevelopment\github_client;
use App\Services\Jira\jira_client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class monitor_ai_development_agent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $executionId)
    {
        $this->onConnection(config('ai_development.queue_connection', 'database'))
            ->onQueue(config('ai_development.queue_name', 'ai-development'));
    }

    public function handle(
        ai_agent_provider_interface $agentProvider,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void {
        $execution = ai_development_execution::query()
            ->with(['issue.connection', 'project.githubConnection', 'project.jiraProject', 'agent'])
            ->find($this->executionId);
        if (! $execution || in_array($execution->status, [ai_development_states::COMPLETED, ai_development_states::REJECTED], true)) {
            return;
        }
        if ($execution->status === ai_development_states::BLOCKED && $execution->github_task_state !== 'completed') {
            return;
        }
        if (! $execution->github_task_id || ! $execution->project?->githubConnection) {
            return;
        }

        try {
            $task = $agentProvider->status(
                $execution->project->githubConnection,
                $execution->project->github_owner,
                $execution->project->github_repository,
                $execution->github_task_id,
            );
            $state = (string) ($task['state'] ?? 'failed');
            $execution->update([
                'github_task_state' => $state,
                'github_task_url' => $task['html_url'] ?? $task['url'] ?? $execution->github_task_url,
                'context' => array_merge((array) $execution->context, ['github_task' => $task]),
                'last_activity_at' => now(),
            ]);

            if (in_array($state, ['queued', 'idle'], true)) {
                $this->reschedule();
                return;
            }
            if ($state === 'in_progress') {
                if ($execution->status === ai_development_states::ANALYZING) {
                    $states->transition($execution, ai_development_states::DEVELOPING, ['github_task_state' => $state]);
                }
                $this->reschedule();
                return;
            }
            if ($state === 'waiting_for_user') {
                $this->block($execution, 'Copilot cloud agent quedo esperando una accion en GitHub.', $states, $notifications);
                return;
            }
            if ($state !== 'completed') {
                $this->fail($execution, 'Copilot cloud agent termino en estado '.$state.'.', $states, $notifications);
                return;
            }

            $this->completeAgentTask($execution, $task, $states);
        } catch (Throwable $exception) {
            $this->fail($execution, 'No fue posible consultar Copilot cloud agent: '.mb_substr($exception->getMessage(), 0, 1200), $states, $notifications);
        }
    }

    private function completeAgentTask(ai_development_execution $execution, array $task, ai_development_state_machine $states): void
    {
        $branch = collect((array) ($task['artifacts'] ?? []))->first(fn (array $artifact): bool => ($artifact['type'] ?? null) === 'branch');
        $headBranch = data_get($branch, 'data.head_ref') ?: $execution->feature_branch;
        $github = new github_client($execution->project->githubConnection);
        $pullRequest = collect($github->openPullRequests(
            $execution->project->github_owner,
            $execution->project->github_repository,
            $headBranch,
            $execution->base_branch,
        ))->first();
        $pullNumber = (int) ($pullRequest['number'] ?? 0);
        if ($pullNumber < 1) {
            throw new RuntimeException('Copilot cloud agent termino sin un pull request visible para la branch '.$headBranch.'.');
        }

        $execution->update([
            'feature_branch' => $headBranch ?: $execution->feature_branch,
            'github_pull_request_number' => $pullNumber,
            'last_commit_sha' => null,
            'error' => null,
            'blocked_reason' => null,
            'context' => array_merge((array) $execution->context, [
                'github_head_branch' => $headBranch ?: data_get($execution->context, 'github_head_branch'),
                'github_pull_request' => $pullNumber,
                'github_pull_request_url' => $pullRequest['html_url'] ?? null,
            ]),
        ]);
        if ($execution->status === ai_development_states::ANALYZING) {
            $states->transition($execution, ai_development_states::TESTING, ['github_task_state' => 'completed']);
        } elseif ($execution->status === ai_development_states::DEVELOPING) {
            $states->transition($execution, ai_development_states::TESTING, ['github_task_state' => 'completed']);
        }
        $states->event($execution->fresh(), 'tests_passed', ['source' => 'github_copilot_cloud_agent', 'task_id' => $execution->github_task_id]);
        $states->transition($execution->fresh(), ai_development_states::INTEGRATING_QA, ['pull_request' => $pullNumber]);

        $merge = $github->mergePullRequest($execution->project->github_owner, $execution->project->github_repository, $pullNumber);
        if (($merge['merged'] ?? false) !== true) {
            throw new RuntimeException('El pull request de Copilot no pudo integrarse hacia QA.');
        }
        (new jira_client($execution->issue->connection))->transitionIssue($execution->jira_key, 'Deployed');
        $execution->update([
            'context' => array_merge((array) $execution->context, ['qa_merged' => true]),
            'last_activity_at' => now(),
        ]);
        $states->transition($execution->fresh(), ai_development_states::WAITING_QA_PIPELINE, ['pull_request' => $pullNumber]);
        monitor_ai_development_pipeline::dispatch($execution->id, 'qa')->delay(now()->addSeconds(5));
    }

    private function fail(
        ai_development_execution $execution,
        string $message,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void {
        $execution = $execution->fresh(['project', 'issue', 'agent']);
        $execution->error = mb_substr($message, 0, 4000);
        $execution->consecutive_failures = (int) $execution->consecutive_failures + 1;
        $execution->save();
        $states->event($execution, 'github_agent_failed', ['message' => mb_substr($message, 0, 1000)]);
        if ($execution->attempt >= max(1, (int) $execution->project?->max_execution_attempts)
            || $execution->consecutive_failures >= max(1, (int) $execution->project?->max_consecutive_failures)) {
            $this->block($execution, $message, $states, $notifications);
            return;
        }
        if ($execution->status !== ai_development_states::FAILED) {
            $states->transition($execution, ai_development_states::FAILED, ['message' => mb_substr($message, 0, 1000)]);
        }
        run_ai_development_execution::dispatch($execution->id)->delay(now()->addSeconds(5));
    }

    private function block(
        ai_development_execution $execution,
        string $reason,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void {
        $states->block($execution, $reason);
        $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $reason);
    }

    private function reschedule(): void
    {
        self::dispatch($this->executionId)->delay(now()->addSeconds(max(10, (int) config('ai_development.pipeline.poll_delay_seconds', 60))));
    }
}