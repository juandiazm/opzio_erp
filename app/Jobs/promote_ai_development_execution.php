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
use RuntimeException;
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
            if (($context['promotion_stage'] ?? 'qa_sync') === 'qa_sync') {
                $pr = collect($github->openPullRequests($project->github_owner, $project->github_repository, 'main', $execution->base_branch))->first()
                    ?: $github->createPullRequest($project->github_owner, $project->github_repository, $execution->jira_key.' synchronize main into qa', 'main', $execution->base_branch, 'Synchronize production before final promotion.');
                $number = (int) ($pr['number'] ?? 0);
                if ($number < 1) {
                    throw new RuntimeException('No fue posible crear el pull request main hacia QA.');
                }
                $merge = $github->mergePullRequest($project->github_owner, $project->github_repository, $number);
                if (($merge['merged'] ?? false) !== true) {
                    throw new RuntimeException('No fue posible integrar main hacia QA.');
                }
                $execution->update(['context' => array_merge($context, ['promotion_stage' => 'qa_sync_pipeline', 'qa_sync_pull_request' => $number])]);
                $states->transition($execution, ai_development_states::WAITING_MAIN_PIPELINE, ['stage' => 'qa_sync', 'pull_request' => $number]);
                monitor_ai_development_pipeline::dispatch($execution->id, 'main')->delay(now()->addSeconds(5));
                return;
            }
            $pr = collect($github->openPullRequests($project->github_owner, $project->github_repository, $execution->base_branch, 'main'))->first()
                ?: $github->createPullRequest($project->github_owner, $project->github_repository, $execution->jira_key.' promote QA to main', $execution->base_branch, 'main', 'Promote QA after Jira Done approval.');
            $number = (int) ($pr['number'] ?? 0);
            if ($number < 1) {
                throw new RuntimeException('No fue posible crear el pull request QA hacia main.');
            }
            $merge = $github->mergePullRequest($project->github_owner, $project->github_repository, $number);
            if (($merge['merged'] ?? false) !== true) {
                throw new RuntimeException('No fue posible integrar QA hacia main.');
            }
            $execution->update(['context' => array_merge($context, ['promotion_stage' => 'main_release_pipeline', 'main_pull_request' => $number])]);
            $states->transition($execution, ai_development_states::WAITING_MAIN_PIPELINE, ['stage' => 'main_release', 'pull_request' => $number]);
            monitor_ai_development_pipeline::dispatch($execution->id, 'main')->delay(now()->addSeconds(5));
        } catch (Throwable $exception) {
            $states->block($execution, 'No fue posible promover la historia: '.mb_substr($exception->getMessage(), 0, 1200));
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
        }
    }
}