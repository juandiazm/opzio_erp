<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_agent;

interface ai_agent_provider_interface
{
    public function execute(ai_agent $agent, string $prompt, string $workspace, array $context = []): array;
}