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
            $states->transition($execution, ai_development_states::WAITING_MAIN_PIPELINE, [
                'stage' => 'main_release',
                'source_branch' => $execution->base_branch,
                'target_branch' => 'main',
                'merge_commit' => $merge['sha'] ?? null,
            ]);
            monitor_ai_development_pipeline::dispatch($execution->id, 'main')->delay(now()->addSeconds(5));
        } catch (Throwable $exception) {
            $states->block($execution, 'No fue posible promover la historia: '.mb_substr($exception->getMessage(), 0, 1200));
            $notifications->blocked($execution->fresh(['project', 'issue', 'agent']), $execution->blocked_reason);
        }
    }
}