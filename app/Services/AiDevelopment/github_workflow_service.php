<?php

namespace App\Services\AiDevelopment;

use App\Models\github_connection;
use App\Models\jira_automation_project;

class github_workflow_service
{
    public function latestForBranch(jira_automation_project $project, string $branch): ?array
    {
        if (! $project->githubConnection || blank($project->github_owner) || blank($project->github_repository)) {
            return null;
        }
        $runs = (new github_client($project->githubConnection))->workflowRuns(
            $project->github_owner,
            $project->github_repository,
            $branch,
            20,
        );
        $run = collect((array) ($runs['workflow_runs'] ?? []))->first();
        if (! is_array($run)) {
            return null;
        }

        return [
            'id' => (string) ($run['id'] ?? ''),
            'name' => (string) ($run['name'] ?? $run['workflow_id'] ?? 'GitHub Actions'),
            'status' => (string) ($run['status'] ?? 'unknown'),
            'conclusion' => $run['conclusion'] ?? null,
            'url' => $run['html_url'] ?? null,
            'head_sha' => $run['head_sha'] ?? null,
            'created_at' => $run['created_at'] ?? null,
        ];
    }

    public function details(jira_automation_project $project, string $runId): array
    {
        $client = new github_client($project->githubConnection);
        $run = $client->workflowRun($project->github_owner, $project->github_repository, $runId);
        return [
            'id' => (string) ($run['id'] ?? $runId),
            'name' => (string) ($run['name'] ?? $run['workflow_id'] ?? 'GitHub Actions'),
            'status' => (string) ($run['status'] ?? 'unknown'),
            'conclusion' => $run['conclusion'] ?? null,
            'url' => $run['html_url'] ?? null,
            'head_sha' => $run['head_sha'] ?? null,
        ];
    }

    public function logs(jira_automation_project $project, string $runId): string
    {
        return (new github_client($project->githubConnection))->workflowLogs(
            $project->github_owner,
            $project->github_repository,
            $runId,
        );
    }
}