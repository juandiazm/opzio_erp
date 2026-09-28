<?php

namespace App\Services\Jira;

use App\Models\jira_connection;
use App\Services\AiDevelopment\jira_automation_statuses;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class jira_client
{
    public function __construct(
        private readonly jira_connection $connection,
        private readonly ?float $requestTimeout = null,
        private readonly ?int $requestRetries = null,
    )
    {
    }

    public function testConnection(): array
    {
        return $this->get('/myself', [], (float) config('jira.test_timeout', 10), 0);
    }

    public function fields(): array
    {
        return $this->get('/field', [], (float) config('jira.test_timeout', 10), 0);
    }

    public function issueTypes(): array
    {
        return $this->get('/issuetype', [], (float) config('jira.test_timeout', 10), 0);
    }

    public function projects(int $startAt = 0, int $maxResults = 50): array
    {
        return $this->get('/project/search', [
            'startAt' => $startAt,
            'maxResults' => $maxResults,
            'orderBy' => 'key',
        ]);
    }

    public function searchIssues(
        string $jql,
        array $fields,
        int $startAt = 0,
        int $maxResults = 50,
        ?string $nextPageToken = null,
    ): array {
        $query = [
            'jql' => $jql,
            'fields' => implode(',', $fields),
            'maxResults' => $maxResults,
        ];
        if ($nextPageToken !== null) {
            $query['nextPageToken'] = $nextPageToken;
        } else {
            $query['startAt'] = $startAt;
        }

        $response = $this->request()->get('/search/jql', $query);
        if (in_array($response->status(), [404, 410], true)) {
            unset($query['nextPageToken']);
            $query['startAt'] = $startAt;
            $response = $this->request()->get('/search', $query);
        }
        $this->ensureSuccessful($response);

        return $this->json($response);
    }

    public function issue(string $issueKey, array $fields): array
    {
        return $this->get('/issue/'.rawurlencode($issueKey), [
            'fields' => implode(',', $fields),
        ]);
    }

    public function worklogs(string $issueKey, int $startAt = 0, int $maxResults = 100): array
    {
        return $this->get('/issue/'.rawurlencode($issueKey).'/worklog', [
            'startAt' => $startAt,
            'maxResults' => $maxResults,
        ]);
    }

    public function changelog(string $issueKey, int $startAt = 0, int $maxResults = 100): array
    {
        return $this->get('/issue/'.rawurlencode($issueKey).'/changelog', [
            'startAt' => $startAt,
            'maxResults' => $maxResults,
        ]);
    }

    public function comments(string $issueKey, int $startAt = 0, int $maxResults = 100): array
    {
        return $this->get('/issue/'.rawurlencode($issueKey).'/comment', [
            'startAt' => $startAt,
            'maxResults' => $maxResults,
            'orderBy' => 'created',
        ]);
    }

    public function updateIssueFields(string $issueKey, array $fields): bool
    {
        $response = $this->request()->put('/issue/'.rawurlencode($issueKey), ['fields' => $fields]);
        $this->ensureSuccessful($response);

        return true;
    }

    public function transitionIssue(string $issueKey, string $statusName): array
    {
        $wanted = jira_automation_statuses::transitionAliases($statusName);
        $currentIssue = $this->issue($issueKey, ['status']);
        $currentStatus = jira_automation_statuses::normalize(data_get($currentIssue, 'fields.status.name'));
        $currentCategory = jira_automation_statuses::normalize(data_get($currentIssue, 'fields.status.statusCategory.name'));
        if (in_array($currentStatus, $wanted, true) || in_array($currentCategory, $wanted, true)) {
            return [
                'id' => null,
                'name' => data_get($currentIssue, 'fields.status.name'),
                'to' => ['name' => data_get($currentIssue, 'fields.status.name')],
                'already_applied' => true,
            ];
        }

        $transitions = $this->get('/issue/'.rawurlencode($issueKey).'/transitions');
        $transition = collect((array) ($transitions['transitions'] ?? []))->first(function (array $item) use ($wanted): bool {
            $names = [
                jira_automation_statuses::normalize($item['name'] ?? null),
                jira_automation_statuses::normalize(data_get($item, 'to.name')),
                jira_automation_statuses::normalize(data_get($item, 'to.statusCategory.name')),
            ];

            return collect($names)->intersect($wanted)->isNotEmpty();
        });
        if (! is_array($transition) || blank($transition['id'] ?? null)) {
            $available = collect((array) ($transitions['transitions'] ?? []))
                ->map(fn (array $item): string => (string) (data_get($item, 'to.name') ?: ($item['name'] ?? '')))
                ->filter()
                ->unique()
                ->implode(', ');
            throw new RuntimeException(sprintf(
                'Jira no ofrece una transicion de "%s" hacia "%s". Disponibles: %s.',
                data_get($currentIssue, 'fields.status.name') ?: 'desconocido',
                $statusName,
                $available ?: 'ninguna',
            ));
        }

        $response = $this->request()->post('/issue/'.rawurlencode($issueKey).'/transitions', [
            'transition' => ['id' => (string) $transition['id']],
        ]);
        $this->ensureSuccessful($response);

        return $transition;
    }

    public function addComment(string $issueKey, string $comment, ?string $mentionAccountId = null): array
    {
        $content = [];
        if (filled($mentionAccountId)) {
            $content[] = ['type' => 'mention', 'attrs' => ['id' => $mentionAccountId, 'text' => '@reporter']];
            $content[] = ['type' => 'text', 'text' => "\n\n"];
        }
        $content[] = ['type' => 'text', 'text' => trim($comment)];
        $body = [
            'body' => [
                'type' => 'doc',
                'version' => 1,
                'content' => [[
                    'type' => 'paragraph',
                    'content' => $content,
                ]],
            ],
        ];
        $response = $this->request()->post('/issue/'.rawurlencode($issueKey).'/comment', $body);
        $this->ensureSuccessful($response);
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    public function get(string $path, array $query = [], ?float $timeout = null, ?int $retries = null): array
    {
        $response = $this->request($timeout, $retries)->get($path, $query);
        $this->ensureSuccessful($response);

        return $this->json($response);
    }

    private function request(?float $timeout = null, ?int $retries = null): PendingRequest
    {
        $siteUrl = rtrim((string) $this->connection->site_url, '/');
        $email = trim((string) $this->connection->credential('email'));
        $token = trim((string) $this->connection->credential('api_token'));

        if ($siteUrl === '' || ! filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('La URL del sitio Jira no es valida.');
        }
        if (! str_starts_with(strtolower($siteUrl), 'https://')) {
            throw new RuntimeException('La URL del sitio Jira debe usar HTTPS.');
        }
        if ($email === '' || $token === '') {
            throw new RuntimeException('La conexion Jira no tiene correo y API token configurados.');
        }

        $timeout ??= $this->requestTimeout ?? (float) config('services.jira.timeout', config('jira.timeout', 30));
        $retries ??= $this->requestRetries ?? (int) config('services.jira.retries', config('jira.retries', 2));

        return Http::baseUrl($siteUrl.'/rest/api/3')
            ->acceptJson()
            ->withBasicAuth($email, $token)
            ->timeout(max(1, $timeout))
            ->retry(max(0, $retries), 500, null, false);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('errorMessages.0')
            ?: $response->json('message')
            ?: $response->reason();
        $message = trim((string) $message);
        if ($message === '') {
            $message = 'Jira devolvio un error HTTP '.$response->status().'.';
        }

        throw new RuntimeException('Jira: '.$message);
    }

    private function json(Response $response): array
    {
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Jira devolvio una respuesta JSON invalida.');
        }

        return $payload;
    }

    public static function safeMessage(Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ($exception instanceof ConnectionException || str_contains(strtolower($message), 'cURL')) {
            return 'No fue posible conectar con Jira. Verifica la URL, la red y el timeout.';
        }
        if (str_contains(strtolower($message), '401') || str_contains(strtolower($message), 'unauthorized')) {
            return 'Jira rechazo las credenciales. Verifica el correo y el API token.';
        }
        if (str_contains(strtolower($message), '403') || str_contains(strtolower($message), 'forbidden')) {
            return 'Las credenciales Jira no tienen permisos suficientes para consultar estos datos.';
        }

        return $message !== '' ? mb_substr($message, 0, 500) : 'No fue posible completar la operacion con Jira.';
    }
}
