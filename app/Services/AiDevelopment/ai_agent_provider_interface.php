<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_agent;
use App\Models\github_connection;

interface ai_agent_provider_interface
{
    public function start(
        ai_agent $agent,
        string $prompt,
        github_connection $connection,
        string $owner,
        string $repository,
        string $baseBranch,
        ?string $headBranch = null,
    ): array;

    public function status(github_connection $connection, string $owner, string $repository, string $taskId): array;
}