<?php

namespace App\Jobs;

use App\Models\ai_development_execution;
use App\Services\AiDevelopment\ai_agent_provider_interface;
use App\Services\AiDevelopment\ai_development_notification_service;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\ai_development_states;
use App\Services\AiDevelopment\github_client;
use App\Services\AiDevelopment\jira_automation_prompt_builder;
use App\Services\AiDevelopment\local_git_service;
use App\Services\Jira\jira_client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class run_ai_development_execution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $executionId)
    {
        $this->onConnection(config('ai_development.queue_connection', 'database'))
            ->onQueue(config('ai_development.queue_name', 'ai-development'));
    }

    public function handle(
        ai_development_state_machine $states,
        jira_automation_prompt_builder $prompts,
        ai_agent_provider_interface $agentProvider,
        local_git_service $git,
        ai_development_notification_service $notifications,
    ): void {
        $execution = ai_development_execution::query()
            ->with(['issue.connection', 'project.jiraProject', 'project.githubConnection', 'project.defaultAgent', 'agent'])
            ->find($this->executionId);
        if (! $execution || in_array($execution->status, [ai_development_states::COMPLETED, ai_development_states::REJECTED], true)) {
            return;
        }
        if ($execution->status === ai_development_states::WAITING_QUALITY_REVIEW) {
            return;
        }

        try {
            $project = $execution->project;
            $agent = $execution->agent ?: $project?->defaultAgent;
            if (! $project || ! $project->enabled || ! $agent || ! $agent->enabled) {
                throw new RuntimeException('El proyecto o el agente ya no esta habilitado.');
            }
            if (! $project->githubConnection || blank($project->github_owner) || blank($project->github_repository)) {
                throw new RuntimeException('El proyecto no tiene una conexion GitHub completa.');
            }
            if ($execution->started_at && $execution->started_at->diffInMinutes(now()) > (int) $project->max_execution_minutes) {
                throw new RuntimeException('Se alcanzo el limite de minutos de la ejecucion.');
            }

            if ((bool) data_get($execution->context, 'qa_merged', false)) {
                if ($execution->status !== ai_development_states::WAITING_QA_PIPELINE) {
                    $states->transition($execution, ai_development_states::WAITING_QA_PIPELINE, ['resumed_after_merge' => true]);
                }
                monitor_ai_development_pipeline::dispatch($execution->id, 'qa')->delay(now()->addSeconds(5));
                return;
            }

            if ($execution->status === ai_development_states::APPROVED || $execution->status === ai_development_states::QUALITY_FEEDBACK || $execution->status === ai_development_states::FAILED) {
                $states->transition($execution, ai_development_states::PREPARING);
            }
            $execution = $execution->fresh(['issue.connection', 'project.jiraProject', 'project.githubConnection', 'project.defaultAgent', 'agent']);
            $execution->attempt = (int) $execution->attempt + 1;
            $execution->consecutive_failures = 0;
            $execution->last_activity_at = now();
            $execution->save();

            $git->prepare($execution, $project->githubConnection, $project->github_owner, $project->github_repository);
            $states->event($execution, 'branch_created', ['branch' => $execution->feature_branch]);
            $states->transition($execution, ai_development_states::ANALYZING);
            $prompt = $prompts->build($execution->issue, $project, $agent, $execution, data_get($execution->context, 'last_feedback', []));
            $states->transition($execution, ai_development_states::PLANNING, ['prompt_hash' => hash('sha256', $prompt)]);
            $states->transition($execution, ai_development_states::DEVELOPING);
            $feedback = (array) data_get($execution->context, 'last_feedback', []);
            $pipelineFeedback = data_get($execution->context, 'pipeline_feedback');
            if (filled($pipelineFeedback)) {
                $feedback[] = ['source' => 'CI/CD', 'content' => $pipelineFeedback];
            }
            $prompt = $prompts->build($execution->issue, $project, $agent, $execution, $feedback);
            $agentResult = $agentProvider->execute($agent, $prompt, $execution->workspace_path, [
                'execution_id' => $execution->id,
                'jira_key' => $execution->jira_key,
                'feature_branch' => $execution->feature_branch,
            ]);
            $execution->update(['context' => array_merge((array) $execution->context, ['agent_output' => $agentResult['output'] ?? null])]);
            $states->transition($execution, ai_development_states::TESTING);
            $commit = $git->commitAndPush($execution, $project->githubConnection, $execution->jira_key.' autonomous development');
            $execution->update(['last_commit_sha' => $commit['commit'] ?? null]);
            $states->event($execution, 'tests_passed', ['changed_files' => $commit['files'] ?? []]);

            $states->transition($execution, ai_development_states::INTEGRATING_QA);
            $github = new github_client($project->githubConnection);
            $pullRequest = collect($github->openPullRequests(
                $project->github_owner,
                $project->github_repository,
                $execution->feature_branch,
                $execution->base_branch,
            ))->first() ?: $github->createPullRequest(
                $project->github_owner,
                $project->github_repository,
                $execution->jira_key.' - '.$execution->issue->summary,
                $execution->feature_branch,
                $execution->base_branch,
                'Automated development for '.$execution->jira_key.'. Created by Opzio ERP; qa.yml and main.yml are protected.',
            );
            $pullRequestNumber = (int) ($pullRequest['number'] ?? 0);
            if ($pullRequestNumber < 1) {
                throw new RuntimeException('GitHub no devolvio un pull request valido hacia QA.');
            }
            $execution->update(['context' => array_merge((array) $execution->context, ['qa_pull_request' => $pullRequestNumber])]);
            $merge = $github->mergePullRequest($project->github_owner, $project->github_repository, $pullRequestNumber);
            if (($merge['merged'] ?? false) !== true) {
                throw new RuntimeException('GitHub no pudo integrar el pull request hacia QA.');
            }
            $execution->update(['context' => array_merge((array) $execution->context, ['qa_pull_request' => $pullRequestNumber, 'qa_merged' => true])]);
            (new jira_client($execution->issue->connection))->transitionIssue($execution->jira_key, 'Deployed');
            $execution->update([
                'context' => array_merge((array) $execution->context, ['qa_pull_request' => $pullRequestNumber]),
                'last_activity_at' => now(),
            ]);
            $states->transition($execution, ai_development_states::WAITING_QA_PIPELINE, ['pull_request' => $pullRequestNumber]);
            monitor_ai_development_pipeline::dispatch($execution->id, 'qa')->delay(now()->addSeconds(5));
        } catch (Throwable $exception) {
            $this->handleFailure($execution, $exception, $states, $notifications);
        }
    }

    private function handleFailure(
        ai_development_execution $execution,
        Throwable $exception,
        ai_development_state_machine $states,
        ai_development_notification_service $notifications,
    ): void {
        $execution = $execution->fresh(['project', 'issue', 'agent']);
        $message = mb_substr(trim($exception->getMessage()) ?: 'Error desconocido durante la ejecucion.', 0, 4000);
        $execution->error = $message;
        $execution->consecutive_failures = (int) $execution->consecutive_failures + 1;
        $execution->last_activity_at = now();
        $execution->save();
        if ($execution->current_phase === ai_development_states::TESTING) {
            $states->event($execution, 'tests_failed', ['message' => $message]);
        }
        $states->event($execution, 'execution_failed', ['message' => $message]);
        $attemptLimit = max(1, (int) $execution->project?->max_execution_attempts);
        $failureLimit = max(1, (int) $execution->project?->max_consecutive_failures);
        if ((int) $execution->attempt >= $attemptLimit || (int) $execution->consecutive_failures >= $failureLimit) {
            $states->block($execution, 'Se alcanzo el limite de intentos de desarrollo: '.$message);
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
            return;
        }
        if ($execution->status !== ai_development_states::FAILED) {
            $states->transition($execution, ai_development_states::FAILED, ['message' => $message]);
        }
        run_ai_development_execution::dispatch($execution->id)->delay(now()->addSeconds(5));
    }
}