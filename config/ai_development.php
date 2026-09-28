<?php

return [
    'approval_ttl_hours' => (int) env('AI_DEVELOPMENT_APPROVAL_TTL_HOURS', 48),
    'workspace_root' => env('AI_DEVELOPMENT_WORKSPACE_ROOT', storage_path('app/ai-development/workspaces')),
    'default_base_branch' => env('AI_DEVELOPMENT_BASE_BRANCH', 'qa'),
    'default_max_execution_attempts' => (int) env('AI_DEVELOPMENT_MAX_EXECUTION_ATTEMPTS', 5),
    'default_max_ci_attempts' => (int) env('AI_DEVELOPMENT_MAX_CI_ATTEMPTS', 3),
    'default_max_execution_minutes' => (int) env('AI_DEVELOPMENT_MAX_EXECUTION_MINUTES', 120),
    'default_max_consecutive_failures' => (int) env('AI_DEVELOPMENT_MAX_CONSECUTIVE_FAILURES', 3),
    'queue_connection' => env('AI_DEVELOPMENT_QUEUE_CONNECTION', 'database'),
    'queue_name' => env('AI_DEVELOPMENT_QUEUE_NAME', 'ai-development'),
    'github' => [
        'base_url' => env('GITHUB_API_BASE_URL', 'https://api.github.com'),
        'timeout' => (float) env('GITHUB_TIMEOUT', 30),
        'retries' => (int) env('GITHUB_RETRIES', 2),
    ],
    'agent' => [
        'command_timeout' => (int) env('AI_DEVELOPMENT_AGENT_COMMAND_TIMEOUT', 3600),
        'default_command' => env('AI_DEVELOPMENT_LUNA_COMMAND'),
    ],
    'pipeline' => [
        'poll_delay_seconds' => (int) env('AI_DEVELOPMENT_PIPELINE_POLL_DELAY', 60),
        'max_log_bytes' => (int) env('AI_DEVELOPMENT_PIPELINE_MAX_LOG_BYTES', 12000),
    ],
];