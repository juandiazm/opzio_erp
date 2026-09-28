<?php

namespace Tests\Feature;

use App\Jobs\run_ai_development_execution;
use App\Http\Controllers\github_controller;
use App\Mail\CustomMail;
use App\Models\ai_agent;
use App\Models\github_connection;
use App\Models\jira_automation_project;
use App\Models\jira_automation_supervisor;
use App\Models\jira_connection;
use App\Models\jira_issue;
use App\Models\jira_project;
use App\Models\jira_user;
use App\Services\AiDevelopment\jira_automation_service;
use App\Services\AiDevelopment\jira_automation_prompt_builder;
use App\Services\AiDevelopment\github_copilot_agent_provider;
use App\Services\Jira\jira_client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TestCase;

class AiDevelopmentFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'queue.default' => 'sync',
            'mail.default' => 'array',
        ]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_08_000001_create_jira_module_tables.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_12_000002_add_client_report_source_fields_to_jira_issues.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_27_000001_create_ai_development_module_tables.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_27_000002_add_supervisor_context_to_ai_development_approvals.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_28_000002_configure_luna_agent.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_28_000003_expand_ai_agent_catalog.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2026_09_28_000004_add_github_agent_task_fields_to_executions.php'), '--realpath' => true]);
    }

    public function test_candidate_detection_is_idempotent_and_rejection_does_not_dispatch(): void
    {
        Mail::fake();
        Queue::fake();
        [$issue, $configuration] = $this->fixture();
        $service = app(jira_automation_service::class);

        $approval = $service->detectCandidate($issue);
        $sameApproval = $service->detectCandidate($issue->fresh(['assignee', 'reporter', 'project', 'connection']));

        $this->assertNotNull($approval);
        $this->assertSame($approval->id, $sameApproval?->id);
        $this->assertDatabaseCount('ai_development_approvals', 1);
        $this->assertDatabaseCount('ai_development_executions', 1);
        Mail::assertQueued(CustomMail::class);
        Queue::assertNothingPushed();

        $token = $this->approvalToken();
        $mailable = null;
        Mail::assertQueued(CustomMail::class, function (CustomMail $queued) use (&$mailable): bool {
            $mailable = $queued;
            return true;
        });
        $this->get((string) ($mailable?->ViewData['approval_url'] ?? ''))->assertOk();
        $result = $service->decideApproval($approval->id, $token, false, 3.5, null, null, null);

        $this->assertSame('rejected', $result['status']);
        $this->assertDatabaseHas('ai_development_approvals', ['id' => $approval->id, 'status' => 'rejected', 'story_point_estimate' => 3.5]);
        $this->assertDatabaseHas('ai_development_executions', ['approval_id' => $approval->id, 'status' => 'rejected']);
        Queue::assertNothingPushed();
        $this->assertTrue($configuration->refresh()->enabled);
    }

    public function test_approval_updates_story_points_transitions_jira_and_dispatches_once(): void
    {
        Mail::fake();
        Queue::fake();
        [$issue] = $this->fixture();
        $service = app(jira_automation_service::class);
        $approval = $service->detectCandidate($issue);
        $token = $this->approvalToken();
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($request->method() === 'PUT' && str_ends_with($path, '/issue/OPS-1')) {
                return Http::response([], 204);
            }
            if ($request->method() === 'GET' && str_ends_with($path, '/issue/OPS-1/transitions')) {
                return Http::response(['transitions' => [['id' => '31', 'name' => 'En curso', 'to' => ['name' => 'En curso']]]]);
            }
            if ($request->method() === 'POST' && str_ends_with($path, '/issue/OPS-1/transitions')) {
                return Http::response([], 204);
            }
            return Http::response([], 200);
        });

        $supervisorContext = 'Prioriza la compatibilidad con la API existente y agrega pruebas para el caso sin permisos.';
        $result = $service->decideApproval($approval->id, $token, true, 8, null, 'Aprobado con contexto adicional.', null, $supervisorContext);

        $this->assertSame('approved', $result['status']);
        $this->assertDatabaseHas('ai_development_approvals', ['id' => $approval->id, 'status' => 'approved', 'story_point_estimate' => 8]);
        $this->assertDatabaseHas('ai_development_approvals', ['id' => $approval->id, 'supervisor_context' => $supervisorContext]);
        $this->assertDatabaseHas('ai_development_executions', ['approval_id' => $approval->id, 'status' => 'approved', 'agent_id' => $result['execution']->agent_id]);
        $this->assertDatabaseHas('jira_issues', ['id' => $issue->id, 'story_points' => 8]);
        Queue::assertPushed(run_ai_development_execution::class, 1);
        $execution = $result['execution']->fresh(['issue', 'project.jiraProject', 'agent']);
        $prompt = app(jira_automation_prompt_builder::class)->build($execution->issue, $execution->project, $execution->agent, $execution);
        $this->assertStringStartsWith('TAREA TECNICA: [OPS-1] Implementar flujo autonomo', $prompt);
        $this->assertStringContainsString($supervisorContext, $prompt);
        $this->assertStringContainsString('<SUPERVISOR_CONTEXT>', $prompt);
        $this->assertSame('OPS-1', $execution->feature_branch);
        $this->assertStringNotContainsString('Assignee:', $prompt);
        $this->assertStringNotContainsString('Reporter:', $prompt);
        Http::assertSent(fn ($request): bool => $request->method() === 'PUT' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/issue/OPS-1'));
    }

    public function test_only_enabled_type_assignee_and_project_are_candidates(): void
    {
        [$issue, $configuration] = $this->fixture();
        $service = app(jira_automation_service::class);

        $issue->update(['issue_type' => 'Bug']);
        $this->assertNull($service->detectCandidate($issue->fresh(['assignee', 'reporter', 'project', 'connection'])));

        $issue->update(['issue_type' => 'Story', 'assignee_jira_user_id' => null, 'jira_updated_at' => now()->addMinute()]);
        $this->assertNull($service->detectCandidate($issue->fresh(['assignee', 'reporter', 'project', 'connection'])));

        $issue->update(['assignee_jira_user_id' => $configuration->assignees()->whereNotNull('jira_user_id')->value('jira_user_id'), 'jira_updated_at' => now()->addMinutes(2)]);
        $configuration->update(['enabled' => false]);
        $this->assertNull($service->detectCandidate($issue->fresh(['assignee', 'reporter', 'project', 'connection'])));
    }

    public function test_to_do_is_treated_as_pending_for_candidate_detection(): void
    {
        Mail::fake();
        Queue::fake();
        [$issue] = $this->fixture();
        $issue->update(['status' => 'To Do']);

        $approval = app(jira_automation_service::class)->detectCandidate($issue->fresh(['assignee', 'reporter', 'project', 'connection']));

        $this->assertNotNull($approval);
        $this->assertDatabaseHas('ai_development_approvals', ['jira_issue_id' => $issue->id, 'status' => 'pending']);
        Queue::assertNothingPushed();
    }

    public function test_jira_tasks_to_do_status_category_name_is_treated_as_pending(): void
    {
        Mail::fake();
        Queue::fake();
        [$issue] = $this->fixture();
        $issue->update(['status' => 'Tareas por hacer']);

        $approval = app(jira_automation_service::class)->detectCandidate($issue->fresh(['assignee', 'reporter', 'project', 'connection']));

        $this->assertNotNull($approval);
        $this->assertDatabaseHas('ai_development_approvals', ['jira_issue_id' => $issue->id, 'status' => 'pending']);
        Queue::assertNothingPushed();
    }

    public function test_approval_token_cannot_be_replayed(): void
    {
        Mail::fake();
        [$issue] = $this->fixture();
        $service = app(jira_automation_service::class);
        $approval = $service->detectCandidate($issue);
        $token = $this->approvalToken();
        $service->decideApproval($approval->id, $token, false, 2, null, null, null);

        $this->expectException(RuntimeException::class);
        $service->decideApproval($approval->id, $token, false, 2, null, null, null);
    }

    public function test_github_module_data_exposes_dashboard_sections_without_secrets(): void
    {
        [$issue] = $this->fixture();
        app(jira_automation_service::class)->detectCandidate($issue);
        Http::fake(function ($request) {
            return str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/rest/api/3/issuetype')
                ? Http::response([
                    ['id' => '1', 'name' => 'Story'],
                    ['id' => '2', 'name' => 'Task'],
                    ['id' => '3', 'name' => 'Bug'],
                    ['id' => '4', 'name' => 'Epic'],
                    ['id' => '5', 'name' => 'Sub-task'],
                ])
                : Http::response([], 200);
        });

        $response = app(github_controller::class)->data(Request::create('/admin/github/data', 'GET'));
        $payload = $response->getData(true);

        $this->assertSame(1, $payload['status']);
        $this->assertArrayHasKey('summary', $payload['data']);
        $this->assertArrayHasKey('approvals', $payload['data']);
        $this->assertArrayHasKey('activity', $payload['data']);
        $this->assertSame(1, $payload['data']['summary']['approvals_pending']);
        $types = $payload['data']['projects'][0]['available_issue_types'];
        $this->assertContains('Task', $types);
        $this->assertContains('Bug', $types);
        $this->assertContains('Epic', $types);
        $this->assertContains('Sub-task', $types);
        $this->assertArrayNotHasKey('token', $payload['data']['github'] ?? []);
        $this->assertArrayNotHasKey('credentials', $payload['data']['github'] ?? []);
    }

    public function test_luna_is_associated_with_the_real_model_name(): void
    {
        $this->assertDatabaseHas('ai_agents', [
            'name' => 'Luna',
            'provider' => 'github_copilot',
            'model' => 'gpt-5.6-luna',
            'is_default' => 1,
        ]);
    }

    public function test_copilot_provider_starts_a_remote_github_agent_task(): void
    {
        [$issue, $configuration] = $this->fixture();
        Http::fake(fn ($request) => Http::response([
            'id' => 'task-remote-1',
            'state' => 'queued',
            'html_url' => 'https://github.com/opzio/erp/agent-sessions/task-remote-1',
        ], 201));

        $agent = ai_agent::where('name', 'Luna')->firstOrFail();
        $task = app(github_copilot_agent_provider::class)->start(
            $agent,
            'Implementa la historia OPS-1 dentro de la rama remota.',
            $configuration->githubConnection,
            'opzio',
            'erp',
            'qa',
            'OP-48',
        );

        $this->assertSame('task-remote-1', $task['id']);
        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/agents/repos/opzio/erp/tasks')
                && $request->data()['model'] === 'gpt-5.6-luna'
                && $request->data()['base_ref'] === 'qa'
                && $request->data()['head_ref'] === 'OP-48'
                && $request->data()['create_pull_request'] === true
                && str_contains($request->data()['prompt'], 'OPS-1');
        });
    }

    public function test_copilot_task_status_falls_back_to_global_endpoint_when_repo_scope_returns_not_found(): void
    {
        [, $configuration] = $this->fixture();
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/agents/repos/opzio/erp/tasks/task-fallback')) {
                return Http::response(['message' => 'Not Found'], 404);
            }
            if (str_ends_with($path, '/agents/tasks/task-fallback')) {
                return Http::response(['id' => 'task-fallback', 'state' => 'completed']);
            }
            return Http::response([], 200);
        });

        $task = app(github_copilot_agent_provider::class)->status(
            $configuration->githubConnection,
            'opzio',
            'erp',
            'task-fallback',
        );

        $this->assertSame('completed', $task['state']);
    }

    public function test_localized_jira_transitions_are_resolved(): void
    {
        [$issue] = $this->fixture();
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/transitions')) {
                return Http::response(['transitions' => [
                    ['id' => '31', 'name' => 'En curso', 'to' => ['name' => 'En curso']],
                    ['id' => '32', 'name' => 'Deploy', 'to' => ['name' => 'Deploy']],
                    ['id' => '33', 'name' => 'Quality', 'to' => ['name' => 'Quality']],
                    ['id' => '34', 'name' => 'Finalizada', 'to' => ['name' => 'Finalizada']],
                ]]);
            }
            return Http::response([], 204);
        });

        $client = new jira_client($issue->connection);
        $this->assertSame('31', $client->transitionIssue('OPS-1', 'In Progress')['id']);
        $this->assertSame('32', $client->transitionIssue('OPS-1', 'Deployed')['id']);
        $this->assertSame('33', $client->transitionIssue('OPS-1', 'QA')['id']);
        $this->assertSame('34', $client->transitionIssue('OPS-1', 'Done')['id']);
    }

    private function fixture(): array
    {
        $connection = jira_connection::create([
            'name' => 'Jira test',
            'site_url' => 'https://jira.example.test',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'jira-secret'],
            'settings' => ['story_points_field' => 'customfield_10016'],
        ]);
        $project = jira_project::create([
            'jira_connection_id' => $connection->id,
            'external_id' => '10001',
            'project_key' => 'OPS',
            'name' => 'Operacion',
            'status' => 'active',
        ]);
        $assignee = jira_user::create([
            'jira_connection_id' => $connection->id,
            'account_id' => 'jira-user-1',
            'display_name' => 'Ana Jira',
            'active' => true,
        ]);
        $reporter = jira_user::create([
            'jira_connection_id' => $connection->id,
            'account_id' => 'jira-user-2',
            'display_name' => 'Reporter Jira',
            'active' => true,
        ]);
        $issue = jira_issue::create([
            'jira_connection_id' => $connection->id,
            'jira_project_id' => $project->id,
            'external_id' => '20001',
            'issue_key' => 'OPS-1',
            'issue_type' => 'Story',
            'summary' => 'Implementar flujo autonomo',
            'description' => 'Construir el flujo de desarrollo.',
            'status' => 'Pending',
            'assignee_jira_user_id' => $assignee->id,
            'reporter_jira_user_id' => $reporter->id,
            'jira_updated_at' => now(),
            'comments' => [],
            'raw_fields' => [],
        ]);
        $agent = ai_agent::query()->where('name', 'Luna')->firstOrFail();
        $github = github_connection::create([
            'name' => 'GitHub test',
            'base_url' => 'https://api.github.com',
            'status' => 'active',
            'credentials' => ['token' => 'github-secret'],
        ]);
        $configuration = jira_automation_project::create([
            'jira_project_id' => $project->id,
            'github_connection_id' => $github->id,
            'github_owner' => 'opzio',
            'github_repository' => 'erp',
            'default_agent_id' => $agent->id,
            'enabled' => true,
            'base_branch' => 'qa',
            'max_execution_attempts' => 5,
            'max_ci_attempts' => 3,
            'max_execution_minutes' => 120,
            'max_consecutive_failures' => 3,
        ]);
        $configuration->issueTypes()->create(['issue_type' => 'Story', 'enabled' => true]);
        $configuration->assignees()->create(['jira_user_id' => $assignee->id, 'assignee_key' => $assignee->account_id, 'display_name' => $assignee->display_name, 'enabled' => true]);
        jira_automation_supervisor::create(['email' => 'supervisor@example.test', 'name' => 'Supervisor', 'enabled' => true]);

        return [$issue->fresh(['assignee', 'reporter', 'project', 'connection']), $configuration];
    }

    private function approvalToken(): string
    {
        $mailable = null;
        Mail::assertQueued(CustomMail::class, function (CustomMail $queued) use (&$mailable): bool {
            $mailable = $queued;
            return true;
        });
        $url = (string) ($mailable?->ViewData['approval_url'] ?? '');
        return (string) Str::of(parse_url($url, PHP_URL_PATH) ?: '')->afterLast('/');
    }
}