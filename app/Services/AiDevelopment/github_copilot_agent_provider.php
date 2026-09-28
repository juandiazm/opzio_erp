<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_agent;
use App\Models\github_connection;

class github_copilot_agent_provider implements ai_agent_provider_interface
{
    public function start(
        ai_agent $agent,
        string $prompt,
        github_connection $connection,
        string $owner,
        string $repository,
        string $baseBranch,
        ?string $headBranch = null,
    ): array {
        return (new github_client($connection))->startAgentTask(
            $owner,
            $repository,
            [
                'prompt' => $prompt,
                'model' => trim((string) $agent->model) ?: null,
                'create_pull_request' => true,
                'base_ref' => $baseBranch,
                'head_ref' => $headBranch,
            ],
        );
    }

    public function status(github_connection $connection, string $owner, string $repository, string $taskId): array
    {
        return (new github_client($connection))->agentTask($owner, $repository, $taskId);
    }
}