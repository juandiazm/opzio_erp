<?php

namespace App\Services\AiDevelopment;

use App\Models\github_connection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class github_client
{
    public function __construct(private readonly github_connection $connection)
    {
    }

    public function testConnection(): array
    {
        return $this->get('/user');
    }

    public function repository(string $owner, string $repository): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository));
    }

    public function branch(string $owner, string $repository, string $branch): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/branches/'.rawurlencode($branch));
    }

    public function createBranch(string $owner, string $repository, string $branch, string $baseBranch): array
    {
        $path = '/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/git/ref/heads/'.rawurlencode($branch);
        $existing = $this->request()->get($path);
        if ($existing->successful()) {
            $payload = $existing->json();
            return is_array($payload) ? $payload : [];
        }
        if ($existing->status() !== 404) {
            $this->ensureSuccessful($existing);
        }
        $base = $this->branch($owner, $repository, $baseBranch);
        return $this->json('POST', '/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/git/refs', [
            'ref' => 'refs/heads/'.$branch,
            'sha' => (string) data_get($base, 'commit.sha'),
        ]);
    }

    public function createPullRequest(string $owner, string $repository, string $title, string $head, string $base, string $body = '', bool $draft = false): array
    {
        return $this->json('POST', '/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/pulls', [
            'title' => $title,
            'head' => $head,
            'base' => $base,
            'body' => $body,
            'draft' => $draft,
        ]);
    }

    public function openPullRequests(string $owner, string $repository, string $head, string $base): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/pulls', [
            'state' => 'open',
            'head' => $owner.':'.$head,
            'base' => $base,
            'per_page' => 10,
        ]);
    }

    public function mergePullRequest(string $owner, string $repository, int $pullRequestNumber, string $method = 'merge'): array
    {
        return $this->json('PUT', '/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/pulls/'.$pullRequestNumber.'/merge', [
            'merge_method' => in_array($method, ['merge', 'squash', 'rebase'], true) ? $method : 'merge',
        ]);
    }

    public function workflowRuns(string $owner, string $repository, ?string $branch = null, int $perPage = 10): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/actions/runs', array_filter([
            'branch' => $branch,
            'per_page' => min(100, max(1, $perPage)),
        ], fn ($value): bool => $value !== null && $value !== ''));
    }

    public function startAgentTask(string $owner, string $repository, array $payload): array
    {
        $payload = array_filter($payload, fn ($value): bool => $value !== null && $value !== '');
        return $this->json('POST', '/agents/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/tasks', $payload);
    }

    public function agentTask(string $owner, string $repository, string $taskId): array
    {
        $path = '/agents/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/tasks/'.rawurlencode($taskId);
        $response = $this->request()->get($path);
        if ($response->status() === 404) {
            return $this->get('/agents/tasks/'.rawurlencode($taskId));
        }
        $this->ensureSuccessful($response);
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    public function commits(string $owner, string $repository, ?string $branch = null, int $perPage = 10): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/commits', array_filter([
            'sha' => $branch,
            'per_page' => min(100, max(1, $perPage)),
        ], fn ($value): bool => $value !== null && $value !== ''));
    }

    public function checkRuns(string $owner, string $repository, string $ref, int $perPage = 100): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/commits/'.rawurlencode($ref).'/check-runs', [
            'per_page' => min(100, max(1, $perPage)),
        ]);
    }

    public function workflowRun(string $owner, string $repository, string|int $runId): array
    {
        return $this->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/actions/runs/'.rawurlencode((string) $runId));
    }

    public function workflowLogs(string $owner, string $repository, string|int $runId): string
    {
        $response = $this->request()->get('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/actions/runs/'.rawurlencode((string) $runId).'/logs');
        $this->ensureSuccessful($response);

        return mb_substr($response->body(), 0, (int) config('ai_development.pipeline.max_log_bytes', 12000));
    }

    public function deleteBranch(string $owner, string $repository, string $branch): bool
    {
        $response = $this->request()->delete('/repos/'.rawurlencode($owner).'/'.rawurlencode($repository).'/git/refs/heads/'.rawurlencode($branch));
        if ($response->status() === 404) {
            return true;
        }
        $this->ensureSuccessful($response);

        return true;
    }

    public function get(string $path, array $query = []): array
    {
        $response = $this->request()->get($path, $query);
        $this->ensureSuccessful($response);
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('GitHub devolvio una respuesta JSON invalida.');
        }

        return $payload;
    }

    private function json(string $method, string $path, array $payload): array
    {
        $response = $this->request()->send($method, $path, ['json' => $payload]);
        $this->ensureSuccessful($response);
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function request()
    {
        $baseUrl = rtrim((string) ($this->connection->base_url ?: config('ai_development.github.base_url')), '/');
        $token = trim((string) $this->connection->credential('token'));
        if ($baseUrl === '' || ! filter_var($baseUrl, FILTER_VALIDATE_URL) || ! str_starts_with(strtolower($baseUrl), 'https://')) {
            throw new RuntimeException('La URL de GitHub no es valida o no usa HTTPS.');
        }
        if ($token === '') {
            throw new RuntimeException('La conexion GitHub no tiene un token configurado.');
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'Opzio-ERP-AI-Development'])
            ->withToken($token)
            ->timeout(max(1, (float) config('ai_development.github.timeout', 30)))
            ->retry(max(0, (int) config('ai_development.github.retries', 2)), 500, null, false);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }
        $message = trim((string) ($response->json('message') ?: $response->reason()));
        throw new RuntimeException('GitHub: '.mb_substr($message !== '' ? $message : 'error HTTP '.$response->status(), 0, 500));
    }

    public static function safeMessage(Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ($exception instanceof ConnectionException || str_contains(strtolower($message), 'curl')) {
            return 'No fue posible conectar con GitHub. Verifica la red y el timeout.';
        }
        if (str_contains(strtolower($message), '401') || str_contains(strtolower($message), 'bad credentials')) {
            return 'GitHub rechazo las credenciales configuradas.';
        }
        if (str_contains(strtolower($message), '403') || str_contains(strtolower($message), 'forbidden')) {
            return 'Las credenciales GitHub no tienen permisos suficientes.';
        }

        return mb_substr($message !== '' ? $message : 'No fue posible completar la operacion con GitHub.', 0, 500);
    }
}