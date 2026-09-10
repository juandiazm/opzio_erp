<?php

namespace App\traits;

use App\Models\jira_connection;
use App\Services\Jira\jira_sync_service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait jira_configuration_trait
{
    protected function Jira_Authorize(): void
    {
        $permission = collect(session('app_permissions', []))->firstWhere('url', 'admin/jira/');
        abort_unless($permission && collect(session('permissions', []))->firstWhere('user_permission_id', $permission['id'] ?? $permission->id ?? null), 403);
    }

    public function Jira_SaveConnection(Request $request): array
    {
        $this->Jira_Authorize();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'site_url' => ['required', 'url', 'max:255', 'starts_with:https://'],
            'email' => ['required', 'email', 'max:255'],
            'api_token' => ['nullable', 'string', 'max:5000'],
            'timezone' => ['nullable', 'string', 'max:80'],
        ]);
        $connection = jira_connection::withTrashed()->latest('updated_at')->first() ?? new jira_connection();
        if ($connection->trashed()) {
            $connection->restore();
        }
        $credentials = (array) $connection->credentials;
        if (filled($data['api_token'] ?? null)) {
            $credentials['api_token'] = trim((string) $data['api_token']);
        }
        if (blank($credentials['api_token'] ?? null)) {
            throw ValidationException::withMessages(['api_token' => 'El API token es obligatorio para una conexion nueva.']);
        }
        $settings = collect((array) $connection->settings)->except('sync_days')->all();
        $settings['timezone'] = trim((string) ($data['timezone'] ?? config('jira.default_timezone', 'America/Bogota')));
        $connection->fill([
            'name' => trim($data['name']),
            'site_url' => rtrim(trim($data['site_url']), '/'),
            'provider' => 'jira_cloud',
            'status' => $connection->exists ? $connection->status : 'draft',
            'credentials' => $credentials + ['email' => trim($data['email'])],
            'settings' => $settings,
        ]);
        $connection->credentials = array_merge($credentials, ['email' => trim($data['email'])]);
        if (! $connection->exists) {
            $connection->created_by_user_id = data_get(session('user'), 'id');
        }
        $connection->save();

        return [
            'id' => $connection->id,
            'message' => 'Conexion Jira guardada correctamente.',
            'connection' => $this->Jira_ConnectionPayload($connection),
        ];
    }

    public function Jira_TestConnection(Request $request): array
    {
        $this->Jira_Authorize();
        return app(jira_sync_service::class)->test($this->Jira_GetConnection());
    }

    public function Jira_SyncConnection(Request $request): array
    {
        $this->Jira_Authorize();
        $data = $request->validate([
            'mode' => ['required', Rule::in(['full', 'updated'])],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'full' => ['nullable', 'boolean'],
            'start_at' => ['nullable', 'integer', 'min:0'],
            'next_page_token' => ['nullable', 'string', 'max:500'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        return app(jira_sync_service::class)->syncBatch(
            $this->Jira_GetConnection(),
            (int) ($data['days'] ?? 1),
            (int) config('jira.web_batch_size', 5),
            (int) ($data['start_at'] ?? 0),
            $data['next_page_token'] ?? null,
            $data['from'] ?? null,
            $data['to'] ?? null,
            $data['mode'],
        );
    }

    protected function Jira_GetConnection(): jira_connection
    {
        return jira_connection::query()->latest('updated_at')->firstOrFail();
    }

    protected function Jira_ConnectionPayload(jira_connection $connection): array
    {
        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'site_url' => $connection->site_url,
            'provider' => $connection->provider,
            'status' => $connection->status,
            'email' => $connection->credential('email'),
            'timezone' => data_get($connection->settings, 'timezone', config('jira.default_timezone', 'America/Bogota')),
            'last_tested_at' => $connection->last_tested_at?->toIso8601String(),
            'last_sync_at' => $connection->last_sync_at?->toIso8601String(),
            'last_error' => $connection->last_error,
        ];
    }
}
