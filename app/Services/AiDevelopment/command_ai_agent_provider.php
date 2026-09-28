<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_agent;
use RuntimeException;
use Symfony\Component\Process\Process;

class command_ai_agent_provider implements ai_agent_provider_interface
{
    public function execute(ai_agent $agent, string $prompt, string $workspace, array $context = []): array
    {
        $command = trim((string) ($agent->command ?: config('ai_development.agent.default_command')));
        if ($command === '') {
            throw new RuntimeException('El agente no tiene un comando de ejecucion configurado.');
        }
        if (! is_dir($workspace)) {
            throw new RuntimeException('El workspace del agente no existe.');
        }

        $sensitiveEnvironmentKeys = [
            'APP_KEY', 'DB_PASSWORD', 'DB_USERNAME', 'AWS_SECRET_ACCESS_KEY', 'AWS_SESSION_TOKEN',
            'OPENAI_API_KEY', 'GITHUB_TOKEN', 'GH_TOKEN', 'JIRA_API_TOKEN', 'OPZIO_GIT_TOKEN',
        ];
        $environment = array_merge(
            array_diff_key($_ENV, array_flip($sensitiveEnvironmentKeys)),
            array_fill_keys($sensitiveEnvironmentKeys, ''),
            [
            'OPZIO_AI_PROVIDER' => (string) $agent->provider,
            'OPZIO_AI_MODEL' => (string) $agent->model,
            'OPZIO_AI_EXECUTION_ID' => (string) ($context['execution_id'] ?? ''),
            'OPZIO_AI_JIRA_KEY' => (string) ($context['jira_key'] ?? ''),
            'OPZIO_AI_BRANCH' => (string) ($context['feature_branch'] ?? ''),
            ],
        );
        $process = Process::fromShellCommandline(
            $command,
            $workspace,
            $environment,
            null,
            max(60, (int) config('ai_development.agent.command_timeout', 3600)),
        );
        $process->setInput($prompt);
        $process->run();

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());
        if (! $process->isSuccessful()) {
            throw new RuntimeException('El agente termino con error: '.self::safeOutput($errorOutput ?: $output));
        }

        return [
            'output' => self::safeOutput($output),
            'exit_code' => $process->getExitCode(),
        ];
    }

    private static function safeOutput(string $output): string
    {
        $output = preg_replace('/(?:bearer|token|api[_ -]?key|authorization)\s*[:=]\s*[^\s,]+/i', '[redacted]', $output) ?? $output;

        return mb_substr($output, 0, 12000);
    }
}