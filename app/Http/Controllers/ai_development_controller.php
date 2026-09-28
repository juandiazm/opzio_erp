<?php

namespace App\Http\Controllers;

use App\Models\ai_agent;
use App\Models\ai_development_approval;
use App\Models\ai_development_event;
use App\Models\ai_development_execution;
use App\Models\github_connection;
use App\Models\jira_automation_assignee;
use App\Models\jira_automation_project;
use App\Models\jira_automation_supervisor;
use App\Models\jira_issue;
use App\Models\jira_project;
use App\Models\jira_user;
use App\Services\AiDevelopment\github_client;
use App\Services\AiDevelopment\jira_automation_service;
use App\Services\Jira\jira_client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ai_development_controller extends Controller
{
    public function data(Request $request): JsonResponse
    {
        return $this->json(fn (): array => $this->dataPayload());
    }

    public function save_github(Request $request): JsonResponse
    {
        return $this->json(function () use ($request): array {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:150'],
                'base_url' => ['required', 'url', 'max:255', 'starts_with:https://'],
                'token' => ['nullable', 'string', 'max:5000'],
            ]);
            $connection = github_connection::withTrashed()->latest('updated_at')->first() ?? new github_connection();
            if ($connection->trashed()) {
                $connection->restore();
            }
            $credentials = (array) $connection->credentials;
            if (filled($data['token'] ?? null)) {
                $credentials['token'] = trim((string) $data['token']);
            }
            if (blank($credentials['token'] ?? null)) {
                throw ValidationException::withMessages(['token' => 'El token GitHub es obligatorio para una conexion nueva.']);
            }
            $connection->fill([
                'singleton_key' => 1,
                'name' => trim($data['name']),
                'base_url' => rtrim(trim($data['base_url']), '/'),
                'status' => $connection->status ?: 'draft',
                'credentials' => $credentials,
            ]);
            $connection->save();

            return ['message' => 'Conexion GitHub guardada correctamente.'];
        });
    }

    public function test_github(Request $request): JsonResponse
    {
        return $this->json(function (): array {
            $connection = github_connection::query()->latest('updated_at')->firstOrFail();
            try {
                $user = (new github_client($connection))->testConnection();
                $connection->update(['status' => 'active', 'last_tested_at' => now(), 'last_error' => null]);
                return ['message' => 'Conexion GitHub verificada.', 'account' => ['login' => $user['login'] ?? null]];
            } catch (Throwable $exception) {
                $message = github_client::safeMessage($exception);
                $connection->update(['status' => 'error', 'last_tested_at' => now(), 'last_error' => $message]);
                return ['ok' => false, 'message' => $message];
            }
        });
    }

    public function save_agent(Request $request): JsonResponse
    {
        return $this->json(function () use ($request): array {
            $data = $request->validate([
                'id' => ['nullable', 'integer', 'exists:ai_agents,id'],
                'name' => ['required', 'string', 'max:120'],
                'provider' => ['required', Rule::in(['github_copilot'])],
                'model' => ['required', 'string', 'max:160'],
                'description' => ['nullable', 'string', 'max:255'],
                'cost_tier' => ['required', Rule::in(['low', 'medium', 'high'])],
                'enabled' => ['nullable', 'boolean'],
                'is_default' => ['nullable', 'boolean'],
            ]);
            $agent = ai_agent::query()->find($data['id'] ?? null) ?: new ai_agent();
            $attributes = [
                'name' => trim($data['name']),
                'provider' => trim($data['provider']),
                'model' => trim($data['model']),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'cost_tier' => $data['cost_tier'],
                'enabled' => $request->boolean('enabled', true),
                'is_default' => $request->boolean('is_default'),
            ];
            $agent->fill($attributes)->save();
            if ($agent->is_default) {
                ai_agent::query()->where('id', '!=', $agent->id)->update(['is_default' => false]);
            }

            return ['message' => 'Agente guardado correctamente.'];
        });
    }

    public function save_project(Request $request): JsonResponse
    {
        return $this->json(function () use ($request): array {
            $data = $request->validate([
                'jira_project_id' => ['required', 'integer', 'exists:jira_projects,id'],
                'github_connection_id' => ['nullable', 'integer', 'exists:github_connections,id'],
                'github_owner' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]+$/'],
                'github_repository' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_.-]+$/'],
                'default_agent_id' => ['nullable', 'integer', 'exists:ai_agents,id'],
                'enabled' => ['nullable', 'boolean'],
                'base_branch' => ['required', 'string', 'max:200', 'regex:/^[A-Za-z0-9_.\/-]+$/'],
                'max_execution_attempts' => ['required', 'integer', 'min:1', 'max:50'],
                'max_ci_attempts' => ['required', 'integer', 'min:1', 'max:3'],
                'max_execution_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'max_consecutive_failures' => ['required', 'integer', 'min:1', 'max:20'],
                'issue_types' => ['nullable', 'array'],
                'issue_types.*' => ['string', 'max:120'],
                'assignee_keys' => ['nullable', 'array'],
                'assignee_keys.*' => ['string', 'max:190'],
            ]);
            $project = jira_automation_project::updateOrCreate(
                ['jira_project_id' => (int) $data['jira_project_id']],
                collect($data)->except(['issue_types', 'assignee_keys'])->merge(['enabled' => $request->boolean('enabled')])->all(),
            );
            $selectedTypes = collect($data['issue_types'] ?? [])->map(fn ($value): string => trim((string) $value))->filter()->unique();
            $availableTypes = jira_issue::query()->where('jira_project_id', $project->jira_project_id)->whereNotNull('issue_type')->distinct()->pluck('issue_type')->merge($selectedTypes)->filter()->unique();
            foreach ($availableTypes as $type) {
                $project->issueTypes()->updateOrCreate(['issue_type' => $type], ['enabled' => $selectedTypes->contains($type)]);
            }
            $selectedAssignees = collect($data['assignee_keys'] ?? [])->map(fn ($value): string => trim((string) $value))->filter()->unique();
            $project->assignees()->updateOrCreate(['assignee_key' => '__unassigned__'], [
                'is_unassigned' => true,
                'display_name' => 'Unassigned / Sin asignar',
                'enabled' => $selectedAssignees->contains('__unassigned__'),
            ]);
            $userIds = jira_issue::query()->where('jira_project_id', $project->jira_project_id)->whereNotNull('assignee_jira_user_id')->distinct()->pluck('assignee_jira_user_id');
            foreach (jira_user::query()->whereIn('id', $userIds)->get() as $user) {
                $project->assignees()->updateOrCreate(['assignee_key' => $user->account_id], [
                    'jira_user_id' => $user->id,
                    'display_name' => $user->display_name,
                    'is_unassigned' => false,
                    'enabled' => $selectedAssignees->contains($user->account_id),
                ]);
            }

            return ['message' => 'Configuracion del proyecto guardada.', 'project_id' => $project->id];
        });
    }

    public function save_supervisor(Request $request): JsonResponse
    {
        return $this->json(function () use ($request): array {
            $data = $request->validate([
                'id' => ['nullable', 'integer', 'exists:jira_automation_supervisors,id'],
                'jira_automation_project_id' => ['nullable', 'integer', 'exists:jira_automation_projects,id'],
                'name' => ['nullable', 'string', 'max:200'],
                'email' => ['required', 'email', 'max:255'],
                'enabled' => ['nullable', 'boolean'],
            ]);
            $supervisor = jira_automation_supervisor::query()->find($data['id'] ?? null) ?: new jira_automation_supervisor();
            $supervisor->fill([
                'jira_automation_project_id' => $data['jira_automation_project_id'] ?? null,
                'name' => trim((string) ($data['name'] ?? '')) ?: null,
                'email' => strtolower(trim($data['email'])),
                'enabled' => $request->boolean('enabled', true),
            ])->save();

            return ['message' => 'Supervisor guardado correctamente.'];
        });
    }

    public function scan_project(Request $request, jira_automation_service $service): JsonResponse
    {
        return $this->json(function () use ($request, $service): array {
            $data = $request->validate(['jira_automation_project_id' => ['required', 'integer', 'exists:jira_automation_projects,id']]);
            $project = jira_automation_project::findOrFail((int) $data['jira_automation_project_id']);
            return ['message' => 'Escaneo completado.', 'detected' => $service->scanProject($project)];
        });
    }

    public function approval_page(int $approval, string $token): View
    {
        $record = ai_development_approval::query()->with(['issue.project', 'issue.assignee', 'issue.reporter', 'project.defaultAgent', 'selectedAgent'])->whereKey($approval)->where('token_hash', hash('sha256', $token))->firstOrFail();
        return view('ai_development.approval', [
            'approval' => $record,
            'token' => $token,
            'agents' => ai_agent::query()->where('enabled', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function decide_approval(Request $request, int $approval, string $token, jira_automation_service $service)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'story_point_estimate' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'agent_id' => ['nullable', 'integer', 'exists:ai_agents,id'],
            'note' => ['nullable', 'string', 'max:4000'],
            'supervisor_context' => ['nullable', 'string', 'max:10000'],
        ]);
        try {
            $result = $service->decideApproval(
                $approval,
                $token,
                $data['action'] === 'approve',
                array_key_exists('story_point_estimate', $data) && $data['story_point_estimate'] !== null ? (float) $data['story_point_estimate'] : null,
                $data['agent_id'] ?? null,
                $data['note'] ?? null,
                data_get(session('user'), 'id'),
                $data['supervisor_context'] ?? null,
            );
            return redirect()->to(URL::temporarySignedRoute(
                'ai-development.approval',
                now()->addMinutes(10),
                ['approval' => $approval, 'token' => $token],
            ))->with('decision', $result['status']);
        } catch (Throwable $exception) {
            $record = ai_development_approval::query()->with(['issue.project', 'issue.assignee', 'issue.reporter', 'project.defaultAgent', 'selectedAgent'])->whereKey($approval)->firstOrFail();
            return response()->view('ai_development.approval', [
                'approval' => $record,
                'token' => $token,
                'agents' => ai_agent::query()->where('enabled', true)->orderByDesc('is_default')->orderBy('name')->get(),
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    private function dataPayload(): array
    {
        $githubConnection = github_connection::query()->latest('updated_at')->first();
        $projects = jira_project::query()->with('connection')->orderBy('name')->get();
        $configurations = jira_automation_project::query()->with(['issueTypes', 'assignees', 'defaultAgent', 'githubConnection', 'supervisors'])->get()->keyBy('jira_project_id');
        $remoteIssueTypes = $this->jiraIssueTypeCatalog($projects);
        $projectPayload = $projects->map(function (jira_project $project) use ($configurations, $remoteIssueTypes): array {
            $configuration = $configurations->get($project->id);
            $localTypes = jira_issue::query()->where('jira_project_id', $project->id)->whereNotNull('issue_type')->distinct()->orderBy('issue_type')->pluck('issue_type');
            $types = $localTypes->merge($remoteIssueTypes[$project->id] ?? [])->filter()->unique()->sort()->values();
            $userIds = jira_issue::query()->where('jira_project_id', $project->id)->whereNotNull('assignee_jira_user_id')->distinct()->pluck('assignee_jira_user_id');
            $users = jira_user::query()->whereIn('id', $userIds)->orderBy('display_name')->get();
            return [
                'id' => $project->id,
                'project_key' => $project->project_key,
                'name' => $project->name,
                'available_issue_types' => $types->all(),
                'available_assignees' => collect([['key' => '__unassigned__', 'label' => 'Unassigned / Sin asignar']])->merge($users->map(fn (jira_user $user): array => ['key' => $user->account_id, 'label' => $user->display_name]))->values()->all(),
                'configuration' => $configuration ? [
                    'id' => $configuration->id,
                    'enabled' => $configuration->enabled,
                    'github_connection_id' => $configuration->github_connection_id,
                    'github_owner' => $configuration->github_owner,
                    'github_repository' => $configuration->github_repository,
                    'default_agent_id' => $configuration->default_agent_id,
                    'base_branch' => $configuration->base_branch,
                    'max_execution_attempts' => $configuration->max_execution_attempts,
                    'max_ci_attempts' => $configuration->max_ci_attempts,
                    'max_execution_minutes' => $configuration->max_execution_minutes,
                    'max_consecutive_failures' => $configuration->max_consecutive_failures,
                    'issue_types' => $configuration->issueTypes->where('enabled', true)->pluck('issue_type')->values()->all(),
                    'assignee_keys' => $configuration->assignees->where('enabled', true)->pluck('assignee_key')->values()->all(),
                ] : null,
            ];
        })->values()->all();

        $executionRecords = ai_development_execution::query()
            ->with(['issue', 'project.jiraProject', 'agent'])
            ->latest('updated_at')
            ->limit(100)
            ->get();
        $approvalRecords = ai_development_approval::query()
            ->with(['issue.project', 'project.jiraProject', 'selectedAgent', 'decider'])
            ->latest('updated_at')
            ->limit(100)
            ->get();
        $activeStatuses = ['candidate', 'awaiting_approval', 'approved', 'preparing', 'analyzing', 'planning', 'developing', 'testing', 'fixing', 'integrating_qa', 'waiting_qa_pipeline', 'waiting_quality_review', 'quality_feedback', 'integrating_main', 'waiting_main_pipeline'];
        $summary = [
            'projects_total' => $projects->count(),
            'projects_enabled' => $configurations->where('enabled', true)->count(),
            'approvals_pending' => ai_development_approval::query()->where('status', 'pending')->count(),
            'executions_active' => ai_development_execution::query()->whereIn('status', $activeStatuses)->count(),
            'executions_blocked' => ai_development_execution::query()->where('status', 'blocked')->count(),
            'executions_completed' => ai_development_execution::query()->where('status', 'completed')->count(),
        ];

        return [
            'github' => $githubConnection ? [
                'id' => $githubConnection->id,
                'name' => $githubConnection->name,
                'base_url' => $githubConnection->base_url,
                'status' => $githubConnection->status,
                'token_configured' => filled($githubConnection->credential('token')),
                'last_tested_at' => $githubConnection->last_tested_at?->toIso8601String(),
                'last_error' => $githubConnection->last_error,
            ] : null,
            'summary' => $summary,
            'agents' => ai_agent::query()->orderByDesc('is_default')->orderBy('name')->get()->map(fn (ai_agent $agent): array => [
                'id' => $agent->id,
                'name' => $agent->name,
                'provider' => $agent->provider,
                'model' => $agent->model,
                'description' => $agent->description,
                'cost_tier' => $agent->cost_tier,
                'cost_label' => $this->costLabel($agent->cost_tier),
                'enabled' => $agent->enabled,
                'is_default' => $agent->is_default,
                'execution_provider' => 'GitHub Copilot cloud agent',
            ])->values()->all(),
            'projects' => $projectPayload,
            'supervisors' => jira_automation_supervisor::query()->with('project.jiraProject')->latest('id')->get()->map(fn (jira_automation_supervisor $item): array => [
                'id' => $item->id,
                'project_id' => $item->jira_automation_project_id,
                'project' => $item->project?->jiraProject?->project_key ?: 'Global',
                'name' => $item->name,
                'email' => $item->email,
                'enabled' => $item->enabled,
            ])->values()->all(),
            'approvals' => $approvalRecords->map(fn (ai_development_approval $item): array => [
                'id' => $item->id,
                'jira_key' => $item->issue?->issue_key ?: data_get($item->snapshot, 'jira_key'),
                'summary' => $item->issue?->summary ?: data_get($item->snapshot, 'title'),
                'project' => $item->project?->jiraProject?->project_key ?: data_get($item->snapshot, 'project_key'),
                'issue_type' => $item->issue?->issue_type ?: data_get($item->snapshot, 'issue_type'),
                'jira_status' => $item->issue?->status ?: data_get($item->snapshot, 'status'),
                'status' => $item->status,
                'story_point_estimate' => $item->story_point_estimate,
                'agent' => $item->selectedAgent?->name,
                'decider' => $item->decider?->complete_name ?: $item->decider?->name,
                'decision_note' => $item->decision_note,
                'supervisor_context' => $item->supervisor_context,
                'created_at' => $item->created_at?->toIso8601String(),
                'decided_at' => $item->decided_at?->toIso8601String(),
                'expires_at' => $item->expires_at?->toIso8601String(),
            ])->values()->all(),
            'executions' => $executionRecords->map(fn (ai_development_execution $item): array => [
                'id' => $item->id,
                'jira_key' => $item->jira_key,
                'summary' => $item->issue?->summary,
                'project' => $item->project?->jiraProject?->project_key,
                'agent' => $item->agent?->name,
                'status' => $item->status,
                'phase' => $item->current_phase,
                'branch' => $item->feature_branch,
                'attempt' => $item->attempt,
                'ci_attempts' => $item->ci_attempts,
                'main_ci_attempts' => $item->main_ci_attempts,
                'github_task_id' => $item->github_task_id,
                'github_task_state' => $item->github_task_state,
                'github_task_url' => $item->github_task_url,
                'github_pull_request_number' => $item->github_pull_request_number,
                'started_at' => $item->started_at?->toIso8601String(),
                'last_activity_at' => $item->last_activity_at?->toIso8601String(),
                'error' => $item->error,
                'blocked_reason' => $item->blocked_reason,
            ])->values()->all(),
            'activity' => ai_development_event::query()
                ->with('execution')
                ->latest('created_at')
                ->limit(50)
                ->get()
                ->map(fn (ai_development_event $event): array => [
                    'event' => $event->event,
                    'phase' => $event->phase,
                    'jira_key' => $event->execution?->jira_key,
                    'execution_id' => $event->execution_id,
                    'metadata' => $this->safeContext((array) $event->metadata),
                    'created_at' => $event->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function json(callable $callback): JsonResponse
    {
        try {
            $data = $callback();
            if (($data['ok'] ?? true) === false) {
                return response()->json(['status' => 0, 'message' => $data['message'] ?? 'La operacion no fue exitosa.', 'data' => $data], 422);
            }
            return response()->json(['status' => 1, 'data' => $data]);
        } catch (ValidationException $exception) {
            return response()->json(['status' => 0, 'message' => 'La informacion enviada no es valida.', 'errors' => $exception->errors()], 422);
        } catch (Throwable $exception) {
            logger()->error('AI development request failed.', ['message' => mb_substr($exception->getMessage(), 0, 1000)]);
            return response()->json(['status' => 0, 'message' => $exception->getMessage()], 422);
        }
    }

    private function safeContext(array $context): array
    {
        unset($context['token'], $context['api_token'], $context['credentials'], $context['secret'], $context['authorization']);

        return $context;
    }

    private function jiraIssueTypeCatalog($projects): array
    {
        $catalog = [];
        foreach ($projects->groupBy('jira_connection_id') as $connectionProjects) {
            $connection = $connectionProjects->first()?->connection;
            if (! $connection) {
                continue;
            }
            try {
                $types = (new jira_client($connection))->issueTypes();
                $names = collect($types)
                    ->map(fn ($type): string => trim((string) data_get($type, 'name', '')))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                foreach ($connectionProjects as $project) {
                    $catalog[$project->id] = $names;
                }
            } catch (Throwable $exception) {
                logger()->warning('No fue posible actualizar el catalogo de tipos Jira para GitHub.', [
                    'jira_connection_id' => $connection->id,
                    'message' => jira_client::safeMessage($exception),
                ]);
            }
        }

        return $catalog;
    }

    private function costLabel(?string $tier): string
    {
        return match ($tier) {
            'low' => 'Bajo costo',
            'high' => 'Alto costo',
            default => 'Costo medio',
        };
    }
}