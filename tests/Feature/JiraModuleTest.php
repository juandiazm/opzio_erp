<?php

namespace Tests\Feature;

use App\Models\jira_connection;
use App\Models\jira_report;
use App\Models\jira_report_recurrence;
use App\Services\Jira\jira_metrics_service;
use App\Services\Jira\jira_report_recurrence_service;
use App\Services\Jira\jira_report_service;
use App\Services\Jira\jira_sync_service;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Tests\TestCase;

class JiraModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.jira.timeout' => 2,
            'services.jira.retries' => 0,
        ]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('photo')->nullable();
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
            $table->string('photo')->nullable();
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
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_08_000001_create_jira_module_tables.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_10_000002_add_local_story_point_hours_to_jira.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_12_000001_add_dashboard_filters_to_jira_reports.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_12_000003_create_jira_report_recurrences.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_12_000004_add_monthly_execution_day_to_jira_report_recurrences.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_12_000002_add_client_report_source_fields_to_jira_issues.php'),
            '--realpath' => true,
        ]);
    }

    public function test_it_syncs_jira_data_idempotently_and_builds_metrics(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira de prueba',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'draft',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
            'settings' => ['timezone' => 'America/Bogota'],
        ]);

        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            return match (true) {
                str_ends_with($path, '/field') => Http::response([
                    ['id' => 'customfield_10016', 'name' => 'Story Points'],
                    ['id' => 'customfield_10014', 'name' => 'Epic Link'],
                ]),
                str_ends_with($path, '/project/search') => Http::response([
                    'isLast' => true,
                    'values' => [[
                        'id' => '10001',
                        'key' => 'OPS',
                        'name' => 'Operacion',
                        'projectTypeKey' => 'software',
                    ]],
                ]),
                str_ends_with($path, '/search/jql'), str_ends_with($path, '/search') => Http::response([
                    'isLast' => true,
                    'total' => 1,
                    'issues' => [[
                        'id' => '20001',
                        'key' => 'OPS-1',
                        'fields' => [
                            'project' => ['id' => '10001', 'key' => 'OPS'],
                            'issuetype' => ['name' => 'Story'],
                            'summary' => 'Implementar sincronizacion',
                            'description' => [
                                'type' => 'doc',
                                'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Preparar el flujo de sincronizacion.']]]],
                            ],
                            'status' => ['name' => 'Done', 'statusCategory' => ['name' => 'Done']],
                            'priority' => ['name' => 'High'],
                            'assignee' => ['accountId' => 'jira-user-1', 'displayName' => 'Ana Jira', 'active' => true],
                            'reporter' => ['accountId' => 'jira-user-2', 'displayName' => 'Reporte Jira', 'active' => true],
                            'customfield_10016' => 5.5,
                            'created' => '2026-09-01T10:00:00.000+0000',
                            'updated' => '2026-09-05T10:00:00.000+0000',
                            'resolutiondate' => '2026-09-05T10:00:00.000+0000',
                            'timespent' => 7200,
                            'labels' => ['erp'],
                            'components' => [['name' => 'Integraciones']],
                        ],
                    ]],
                ]),
                str_contains($path, '/issue/OPS-1/worklog') => Http::response([
                    'total' => 1,
                    'worklogs' => [[
                        'id' => '30001',
                        'author' => ['accountId' => 'jira-user-1', 'displayName' => 'Ana Jira', 'active' => true],
                        'started' => '2026-09-05T12:00:00.000+0000',
                        'updated' => '2026-09-05T13:00:00.000+0000',
                        'timeSpentSeconds' => 7200,
                    ]],
                ]),
                str_contains($path, '/issue/OPS-1/changelog') => Http::response([
                    'total' => 1,
                    'values' => [[
                        'id' => '40001',
                        'author' => ['accountId' => 'jira-user-1', 'displayName' => 'Ana Jira', 'active' => true],
                        'created' => '2026-09-05T10:00:00.000+0000',
                        'items' => [['field' => 'status', 'fromString' => 'In Progress', 'toString' => 'Done']],
                    ]],
                ]),
                str_contains($path, '/issue/OPS-1/comment') => Http::response([
                    'total' => 1,
                    'comments' => [[
                        'id' => '50001',
                        'author' => ['displayName' => 'Ana Jira'],
                        'created' => '2026-09-05T14:00:00.000+0000',
                        'body' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Resultado confirmado.']]]]],
                    ]],
                ]),
                default => Http::response([], 200),
            };
        });

        $result = app(jira_sync_service::class)->sync($connection, 30);
        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('jira_projects', ['project_key' => 'OPS']);
        $this->assertDatabaseHas('jira_issues', ['issue_key' => 'OPS-1', 'story_points' => 5.5]);
        $this->assertDatabaseHas('jira_issues', ['issue_key' => 'OPS-1', 'estimated_hours' => 5.5, 'estimated_hours_manual' => 0]);
        $syncedIssue = \App\Models\jira_issue::where('issue_key', 'OPS-1')->firstOrFail();
        $this->assertSame('Preparar el flujo de sincronizacion.', $syncedIssue->description);
        $this->assertSame('Resultado confirmado.', $syncedIssue->comments[0]['content']);
        $this->assertDatabaseHas('jira_issue_worklogs', ['external_id' => '30001', 'time_spent_seconds' => 7200]);
        $this->assertDatabaseHas('jira_issue_changelogs', ['external_history_id' => '40001', 'field' => 'status']);
        $this->assertSame('active', $connection->fresh()->status);

        $jiraUser = $connection->users()->where('account_id', 'jira-user-1')->firstOrFail();
        $employee = new \App\Models\employee();
        $employee->name = 'Ana';
        $employee->last_name = 'ERP';
        $employee->photo = 'ana.webp';
        $employee->save();
        $jiraUser->mapping()->create(['employee_id' => $employee->id, 'mapping_source' => 'manual']);

        $metrics = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]);
        $this->assertSame(5.5, $metrics['summary']['story_points']);
        $this->assertSame(5.5, $metrics['summary']['estimated_hours']);
        $this->assertSame(2.0, $metrics['summary']['worklog_hours']);
        $this->assertSame(1, $metrics['summary']['completed_issues']);
        $this->assertSame('Operacion', $metrics['projects'][0]['label']);
        $this->assertSame(url('storage/images/erp/employees/ana.webp'), $metrics['issues'][0]['assignee_avatar']);
        $this->assertSame(url('storage/images/erp/employees/ana.webp'), $metrics['users'][0]['avatar']);
        $this->assertSame('Operacion', $metrics['users'][0]['top_project']['label']);
        $this->assertSame(5.5, $metrics['users'][0]['top_project']['story_points']);

        $jiraUser->update(['avatar_url' => 'https://jira.example.test/avatar.png']);
        $jiraMetricsWithAvatar = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]);
        $this->assertSame(url('storage/images/erp/employees/ana.webp'), $jiraMetricsWithAvatar['issues'][0]['assignee_avatar']);
        $this->assertSame(url('storage/images/erp/employees/ana.webp'), $jiraMetricsWithAvatar['users'][0]['avatar']);

        $erpUser = new \App\Models\user();
        $erpUser->name = 'Ana';
        $erpUser->lastname = 'Usuario';
        $erpUser->photo = 'ana-user.webp';
        $erpUser->save();
        $jiraUser->mapping()->update(['employee_id' => null, 'user_id' => $erpUser->id]);
        $employee->photo = null;
        $employee->save();
        $jiraMetricsWithUserPhoto = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]);
        $this->assertSame(url('storage/images/erp/users/ana-user.webp'), $jiraMetricsWithUserPhoto['issues'][0]['assignee_avatar']);
        $this->assertSame(url('storage/images/erp/users/ana-user.webp'), $jiraMetricsWithUserPhoto['users'][0]['avatar']);

        $erpUser->photo = null;
        $erpUser->save();
        $jiraMetricsWithJiraFallback = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]);
        $this->assertSame('https://jira.example.test/avatar.png', $jiraMetricsWithJiraFallback['issues'][0]['assignee_avatar']);
        $this->assertSame('https://jira.example.test/avatar.png', $jiraMetricsWithJiraFallback['users'][0]['avatar']);

        $defaultMetrics = app(jira_metrics_service::class)->dashboard();
        $this->assertSame(now()->startOfMonth()->toDateString(), $defaultMetrics['filters']['from']);

        $ongoingIssue = $connection->projects()->first()->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '20002',
            'issue_key' => 'OPS-2',
            'issue_type' => 'Story',
            'summary' => 'Trabajo en curso',
            'status' => 'In Progress',
            'jira_updated_at' => '2026-09-06 10:00:00',
        ]);
        $ongoingIssue->worklogs()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '30002',
            'started_at' => '2026-09-06 11:00:00',
            'time_spent_seconds' => 7200,
        ]);
        $updatedMetrics = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]);
        $this->assertSame(1, $updatedMetrics['summary']['completed_issues']);
        $this->assertSame(1, $updatedMetrics['summary']['story_issues']);
        $this->assertSame(4.0, $updatedMetrics['summary']['worklog_hours']);

        $filteredMetrics = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
            'statuses' => ['In Progress'],
        ]);
        $this->assertSame(1, $filteredMetrics['summary']['story_issues']);
        $this->assertSame('OPS-2', $filteredMetrics['issues'][0]['key']);

        $multiStatusMetrics = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
            'statuses' => ['Done', 'In Progress'],
        ]);
        $this->assertSame(2, $multiStatusMetrics['summary']['story_issues']);

        $multiRelationMetrics = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
            'project_ids' => [$connection->projects()->firstOrFail()->id],
            'user_ids' => [$jiraUser->id],
        ]);
        $this->assertSame(1, $multiRelationMetrics['summary']['story_issues']);

        app(jira_sync_service::class)->sync($connection->fresh(), 30);
        $this->assertDatabaseCount('jira_projects', 1);
        $this->assertDatabaseCount('jira_issues', 2);
        $this->assertDatabaseCount('jira_issue_worklogs', 2);
    }

    public function test_local_story_point_multiplier_and_manual_hours_override(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira horas locales',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $project = $connection->projects()->create([
            'external_id' => '10002',
            'project_key' => 'HRS',
            'name' => 'Horas locales',
            'status' => 'active',
            'story_point_hours_multiplier' => 2.5,
        ]);
        $issue = $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '20010',
            'issue_key' => 'HRS-1',
            'issue_type' => 'Story',
            'summary' => 'Historia estimada localmente',
            'status' => 'Done',
            'story_points' => 4,
            'estimated_hours' => 10,
            'estimated_hours_manual' => false,
            'jira_created_at' => '2026-09-05 10:00:00',
            'jira_resolved_at' => '2026-09-06 10:00:00',
        ]);

        $metrics = app(jira_metrics_service::class)->dashboard(['from' => '2026-09-01', 'to' => '2026-09-08']);
        $this->assertSame(10.0, $metrics['summary']['estimated_hours']);
        $this->assertSame(10.0, $metrics['issues'][0]['estimated_hours']);

        $issue->update(['estimated_hours' => 7.25, 'estimated_hours_manual' => true]);
        $project->update(['story_point_hours_multiplier' => 4]);
        $metrics = app(jira_metrics_service::class)->dashboard(['from' => '2026-09-01', 'to' => '2026-09-08']);

        $this->assertSame(7.25, $metrics['summary']['estimated_hours']);
        $this->assertTrue($metrics['issues'][0]['estimated_hours_manual']);
    }

    public function test_refresh_client_report_data_requeries_all_user_stories(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira backfill cliente',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $project = $connection->projects()->create([
            'external_id' => '10003',
            'project_key' => 'CLI',
            'name' => 'Cliente',
            'status' => 'active',
        ]);
        $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '20003',
            'issue_key' => 'CLI-1',
            'issue_type' => 'Story',
            'summary' => 'Historia local antigua',
            'jira_created_at' => '2026-09-01 10:00:00',
        ]);

        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            return match (true) {
                str_ends_with($path, '/field') => Http::response([
                    ['id' => 'customfield_10016', 'name' => 'Story Points'],
                ]),
                str_ends_with($path, '/search/jql'), str_ends_with($path, '/search') => Http::response([
                    'isLast' => true,
                    'total' => 1,
                    'issues' => [[
                        'id' => '20003',
                        'key' => 'CLI-1',
                        'fields' => [
                            'project' => ['key' => 'CLI'],
                            'issuetype' => ['name' => 'Story'],
                            'summary' => 'Historia actualizada',
                            'description' => 'Descripcion sincronizada',
                            'comment' => ['comments' => [[
                                'id' => '51001',
                                'author' => ['displayName' => 'Cliente'],
                                'body' => 'Se valido el resultado.',
                            ]]],
                            'status' => ['name' => 'Done', 'statusCategory' => ['name' => 'Done']],
                            'priority' => ['name' => 'Medium'],
                        ],
                    ]],
                ]),
                str_contains($path, '/issue/CLI-1/comment') => Http::response([
                    'total' => 1,
                    'comments' => [[
                        'id' => '51001',
                        'author' => ['displayName' => 'Cliente'],
                        'body' => 'Se valido el resultado.',
                    ]],
                ]),
                str_contains($path, '/issue/CLI-1') => Http::response([
                    'id' => '20003',
                    'key' => 'CLI-1',
                    'fields' => [
                        'project' => ['key' => 'CLI'],
                        'issuetype' => ['name' => 'Story'],
                        'summary' => 'Historia actualizada',
                        'description' => 'Descripcion sincronizada',
                        'status' => ['name' => 'Done', 'statusCategory' => ['name' => 'Done']],
                        'priority' => ['name' => 'Medium'],
                    ],
                ]),
                default => Http::response([], 200),
            };
        });

        $result = app(jira_sync_service::class)->refreshClientReportData($connection);
        $issue = $project->issues()->where('issue_key', 'CLI-1')->firstOrFail();

        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('Descripcion sincronizada', $issue->fresh()->description);
        $this->assertSame('Se valido el resultado.', $issue->fresh()->comments[0]['content']);
    }

    public function test_it_marks_invalid_credentials_as_failed_without_exposing_the_token(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira invalido',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'draft',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        Http::fake([
            'https://demo.atlassian.net/*' => Http::response(['errorMessages' => ['Unauthorized']], 401),
        ]);

        $result = app(jira_sync_service::class)->test($connection);

        $this->assertFalse($result['ok']);
        $this->assertSame('error', $connection->fresh()->status);
        $this->assertStringNotContainsString('secret-token', (string) $connection->fresh()->last_error);
        $this->assertStringContainsString('credenciales', strtolower((string) $result['message']));
    }

    public function test_report_snapshot_honors_selected_sources_and_quality_data(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira snapshot',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
            'last_sync_at' => now(),
        ]);
        $project = $connection->projects()->create([
            'external_id' => '10001',
            'project_key' => 'OPS',
            'name' => 'Operacion',
            'status' => 'active',
        ]);
        $issue = $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '20001',
            'issue_key' => 'OPS-1',
            'issue_type' => 'Story',
            'summary' => 'Issue de snapshot',
            'status' => 'Done',
            'jira_created_at' => '2026-09-04 10:00:00',
            'jira_updated_at' => '2026-09-05 10:00:00',
            'jira_resolved_at' => '2026-09-05 10:00:00',
            'story_points' => 8,
        ]);
        $user = 
            $connection->users()->create(['account_id' => 'jira-user-1', 'display_name' => 'Usuario Jira', 'active' => true]);
        $issue->update(['assignee_jira_user_id' => $user->id]);
        $issue->worklogs()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '30001',
            'jira_user_id' => $user->id,
            'started_at' => '2026-09-05 11:00:00',
            'time_spent_seconds' => 3600,
        ]);

        $snapshot = app(jira_report_service::class)->snapshot([
            'title' => 'Snapshot seleccionado',
            'intention' => 'executive_summary',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-08',
            'jira_project_id' => $project->id,
            'jira_epic_issue_id' => null,
            'data_sources' => ['projects', 'statuses', 'quality'],
            'context_prompt' => null,
        ]);

        $this->assertNull($snapshot['summary']['story_points']);
        $this->assertNull($snapshot['summary']['worklog_hours']);
        $this->assertSame('Operacion', $snapshot['projects'][0]['label']);
        $this->assertNull($snapshot['projects'][0]['story_points']);
        $this->assertNull($snapshot['projects'][0]['hours']);
        $this->assertSame('Done', $snapshot['issues'][0]['status']);
        $this->assertTrue($snapshot['quality']['included']);
        $this->assertFalse($snapshot['erp_relations']['included']);
    }

    public function test_dashboard_and_report_ranges_use_jira_resolution_dates_for_completed_issues(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira fechas fuente',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $project = $connection->projects()->create([
            'external_id' => '10005',
            'project_key' => 'DATES',
            'name' => 'Fechas Jira',
            'status' => 'active',
        ]);
        $jiraDatedIssue = $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '25001',
            'issue_key' => 'DATES-1',
            'issue_type' => 'Story',
            'summary' => 'Fecha Jira dentro del rango',
            'jira_created_at' => '2026-09-02 10:00:00',
            'jira_updated_at' => '2026-09-07 10:00:00',
            'jira_resolved_at' => '2026-09-07 11:00:00',
        ]);
        $erpDatedIssue = $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '25002',
            'issue_key' => 'DATES-2',
            'issue_type' => 'Story',
            'summary' => 'Solo fecha ERP dentro del rango',
            'jira_created_at' => '2026-08-01 10:00:00',
            'jira_updated_at' => '2026-08-02 10:00:00',
        ]);
        $resolvedOnlyIssue = $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '25003',
            'issue_key' => 'DATES-3',
            'issue_type' => 'Story',
            'summary' => 'Solo resolucion Jira dentro del rango',
            'jira_created_at' => '2026-08-01 10:00:00',
            'jira_updated_at' => '2026-08-02 10:00:00',
            'jira_resolved_at' => '2026-09-05 10:00:00',
        ]);
        DB::table('jira_issues')->whereIn('id', [$erpDatedIssue->id, $resolvedOnlyIssue->id])->update([
            'created_at' => '2026-09-05 10:00:00',
            'updated_at' => '2026-09-06 10:00:00',
        ]);
        $jiraDatedIssue->worklogs()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '26001',
            'started_at' => '2026-09-03 11:00:00',
            'created_at' => '2026-08-01 11:00:00',
            'updated_at' => '2026-08-02 11:00:00',
            'time_spent_seconds' => 3600,
        ]);

        $filters = ['from' => '2026-09-01', 'to' => '2026-09-08'];
        $dashboard = app(jira_metrics_service::class)->dashboard($filters);
        $snapshot = app(jira_report_service::class)->snapshot([
            'title' => 'Fechas fuente',
            'intention' => 'executive_summary',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-08',
            'project_ids' => [$project->id],
            'epic_ids' => [],
            'user_ids' => [],
            'statuses' => [],
            'data_sources' => ['story_points', 'worklogs', 'projects', 'epics', 'users', 'statuses'],
            'context_prompt' => null,
        ]);

        $this->assertSame(['DATES-3', 'DATES-1'], collect($dashboard['issues'])->pluck('key')->all());
        $this->assertSame(['DATES-3', 'DATES-1'], collect($snapshot['issues'])->pluck('key')->all());
        $this->assertSame(2, $dashboard['summary']['story_issues']);
        $this->assertSame(1.0, $dashboard['summary']['worklog_hours']);
        $this->assertSame(['2026-09-05', '2026-09-07'], collect($dashboard['daily'])->pluck('date')->all());
        $this->assertSame('completed_issues', $dashboard['trace']['scope']);
        $this->assertSame('jira_resolved_at_with_done_status_fallback', $dashboard['trace']['date_basis']);
    }

    public function test_report_criteria_normalizes_dashboard_filters_and_legacy_values(): void
    {
        $service = app(jira_report_service::class);
        $criteria = $service->validateCriteria([
            'title' => 'Filtros multiples',
            'intention' => 'executive_summary',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-08',
            'project_ids' => ['2', 1, 2],
            'epic_ids' => ['8', 7, 8],
            'user_ids' => ['4', 3, 4],
            'statuses' => [' Done ', 'In Progress', 'Done'],
            'data_sources' => ['projects'],
        ]);

        $this->assertSame([2, 1], $criteria['project_ids']);
        $this->assertSame([8, 7], $criteria['epic_ids']);
        $this->assertSame([4, 3], $criteria['user_ids']);
        $this->assertSame(['Done', 'In Progress'], $criteria['statuses']);
        $this->assertSame(2, $criteria['jira_project_id']);
        $this->assertSame(8, $criteria['jira_epic_issue_id']);

        $legacy = $service->validateCriteria([
            'title' => 'Filtro legacy',
            'intention' => 'executive_summary',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-08',
            'jira_project_id' => 11,
            'jira_epic_issue_id' => 12,
            'data_sources' => ['projects'],
        ]);

        $this->assertSame([11], $legacy['project_ids']);
        $this->assertSame([12], $legacy['epic_ids']);
        $this->assertSame([], $legacy['user_ids']);
        $this->assertSame([], $legacy['statuses']);
    }

    public function test_report_recurrence_normalizes_frequency_and_builds_periods(): void
    {
        $service = app(jira_report_recurrence_service::class);
        $configuration = $service->normalize([
            'frequency_value' => '2',
            'frequency_unit' => 'months',
            'execution_day' => '5',
            'range_value' => '7',
            'range_unit' => 'days',
        ]);
        $runAt = Carbon::parse('2026-09-12 00:10:00');
        $period = $service->reportPeriod($runAt, $configuration['range_value'], $configuration['range_unit']);

        $this->assertSame(2, $configuration['frequency_value']);
        $this->assertSame('months', $configuration['frequency_unit']);
        $this->assertSame(5, $configuration['execution_day']);
        $this->assertSame('2026-09-05', $period['from_date']);
        $this->assertSame('2026-09-11', $period['to_date']);
        $this->assertSame('2026-02-28', $service->addFrequency(Carbon::parse('2026-01-31'), 1, 'months')->toDateString());

        $this->assertSame('2026-10-05', $service->nextRunAt($runAt, 1, 'months', 5)->toDateString());
        $this->assertSame('2026-10-20', $service->nextRunAt($runAt, 1, 'months', 20)->toDateString());
        $this->assertSame('2026-02-28', $service->nextRunAt(Carbon::parse('2026-01-12'), 1, 'months', 31)->toDateString());
        $this->assertSame('2026-09-13', $service->nextRunAt($runAt, 1, 'days')->toDateString());

        $monthlyPeriod = $service->reportPeriod($runAt, 1, 'months');
        $this->assertSame('2026-08-01', $monthlyPeriod['from_date']);
        $this->assertSame('2026-08-31', $monthlyPeriod['to_date']);
    }

    public function test_report_recurrence_persists_template_and_execution_relationship(): void
    {
        $template = jira_report::create([
            'title' => 'Reporte operativo',
            'intention' => 'executive_summary',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-12',
            'data_sources' => ['projects'],
            'status' => 'generated',
        ]);
        $recurrence = jira_report_recurrence::create([
            'template_report_id' => $template->id,
            'frequency_value' => 1,
            'frequency_unit' => 'months',
            'execution_day' => 12,
            'range_value' => 1,
            'range_unit' => 'months',
            'next_run_at' => '2026-10-12 00:10:00',
        ]);
        $template->update(['recurrence_id' => $recurrence->id]);
        $execution = jira_report::create([
            'title' => 'Reporte operativo - 12/10/2026',
            'intention' => 'executive_summary',
            'from_date' => '2026-10-01',
            'to_date' => '2026-10-12',
            'data_sources' => ['projects'],
            'recurrence_id' => $recurrence->id,
            'recurrence_sequence' => 1,
            'status' => 'generating',
        ]);

        $this->assertSame($recurrence->id, $template->fresh()->recurrence_id);
        $this->assertSame($template->id, $recurrence->fresh()->templateReport->id);
        $this->assertSame($recurrence->id, $execution->recurrence->id);
        $this->assertSame(12, $recurrence->execution_day);
        $this->assertStringContainsString('12/10/2026', $execution->title);
    }

    public function test_report_snapshot_applies_multiple_dashboard_filters(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira filtros multiples',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $firstProject = $connection->projects()->create([
            'external_id' => '10001',
            'project_key' => 'OPS',
            'name' => 'Operacion',
            'status' => 'active',
        ]);
        $secondProject = $connection->projects()->create([
            'external_id' => '10002',
            'project_key' => 'CRM',
            'name' => 'Clientes',
            'status' => 'active',
        ]);
        $firstUser = $connection->users()->create(['account_id' => 'jira-user-1', 'display_name' => 'Ana Jira', 'active' => true]);
        $secondUser = $connection->users()->create(['account_id' => 'jira-user-2', 'display_name' => 'Luis Jira', 'active' => true]);
        $firstEpic = $firstProject->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '21001',
            'issue_key' => 'OPS-E1',
            'issue_type' => 'Epic',
            'summary' => 'Operacion comercial',
            'status' => 'In Progress',
        ]);
        $secondEpic = $secondProject->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '21002',
            'issue_key' => 'CRM-E1',
            'issue_type' => 'Epic',
            'summary' => 'Relacion con clientes',
            'status' => 'Done',
        ]);
        $firstIssue = $firstProject->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '22001',
            'issue_key' => 'OPS-1',
            'issue_type' => 'Story',
            'summary' => 'Historia operativa',
            'status' => 'Done',
            'assignee_jira_user_id' => $firstUser->id,
            'epic_jira_issue_id' => $firstEpic->id,
            'story_points' => 5,
            'jira_created_at' => '2026-09-05 10:00:00',
        ]);
        $secondIssue = $secondProject->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '22002',
            'issue_key' => 'CRM-1',
            'issue_type' => 'Story',
            'summary' => 'Historia de clientes',
            'status' => 'In Progress',
            'assignee_jira_user_id' => $secondUser->id,
            'epic_jira_issue_id' => $secondEpic->id,
            'story_points' => 3,
            'jira_created_at' => '2026-09-06 10:00:00',
        ]);
        $firstIssue->worklogs()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '23001',
            'jira_user_id' => $firstUser->id,
            'started_at' => '2026-09-05 11:00:00',
            'time_spent_seconds' => 3600,
        ]);
        $secondIssue->worklogs()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '23002',
            'jira_user_id' => $secondUser->id,
            'started_at' => '2026-09-06 11:00:00',
            'time_spent_seconds' => 7200,
        ]);

        $snapshot = app(jira_report_service::class)->snapshot([
            'title' => 'Reporte filtrado',
            'intention' => 'executive_summary',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-08',
            'project_ids' => [$firstProject->id, $secondProject->id],
            'epic_ids' => [$firstEpic->id, $secondEpic->id],
            'user_ids' => [$firstUser->id, $secondUser->id],
            'statuses' => ['Done', 'In Progress'],
            'data_sources' => ['story_points', 'worklogs', 'projects', 'epics', 'users', 'statuses', 'erp_relations'],
            'context_prompt' => null,
        ]);

        $this->assertSame([$firstProject->id, $secondProject->id], $snapshot['filters']['project_ids']);
        $this->assertSame([$firstEpic->id, $secondEpic->id], $snapshot['filters']['epic_ids']);
        $this->assertSame([$firstUser->id, $secondUser->id], $snapshot['filters']['user_ids']);
        $this->assertSame(['Done', 'In Progress'], $snapshot['filters']['statuses']);
        $this->assertCount(2, $snapshot['issues']);
        $this->assertSame(['CRM - Clientes', 'OPS - Operacion'], collect($snapshot['report']['filters']['projects'])->pluck('label')->all());
        $this->assertSame(['OPS-E1 - Operacion comercial', 'CRM-E1 - Relacion con clientes'], collect($snapshot['report']['filters']['epics'])->pluck('label')->all());
        $this->assertSame(['Ana Jira', 'Luis Jira'], collect($snapshot['report']['filters']['users'])->pluck('label')->all());
        $this->assertCount(2, $snapshot['erp_relations']['projects']);
    }

    public function test_client_report_snapshot_and_normalizer_follow_legacy_structure(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira informe cliente',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $project = $connection->projects()->create([
            'external_id' => '10004',
            'project_key' => 'CLI',
            'name' => 'Cliente',
            'status' => 'active',
        ]);
        $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '24001',
            'issue_key' => 'CLI-1',
            'issue_type' => 'Story',
            'summary' => 'Resultado confirmado',
            'status' => 'Done',
            'priority' => 'High',
            'story_points' => 5,
            'estimated_hours' => 7.25,
            'description' => 'Se implemento el ajuste.',
            'comments' => [['content' => 'Se valido en QA.']],
            'jira_created_at' => '2026-09-05 10:00:00',
        ]);
        $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '24002',
            'issue_key' => 'CLI-2',
            'issue_type' => 'Task',
            'summary' => 'Actividad documentada',
            'status' => 'In Progress',
            'story_points' => 1.5,
            'estimated_hours' => 1.75,
            'jira_created_at' => '2026-09-06 10:00:00',
        ]);

        $service = app(jira_report_service::class);
        $criteria = [
            'title' => 'Cliente - Reporte septiembre',
            'intention' => 'client_report',
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-08',
            'project_ids' => [$project->id],
            'epic_ids' => [],
            'user_ids' => [],
            'statuses' => [],
            'data_sources' => ['story_points', 'projects', 'statuses'],
            'context_prompt' => null,
        ];
        $snapshot = $service->snapshot($criteria);
        $normalized = $service->normalize([
            'report_title' => 'Cliente - Reporte septiembre',
            'executive_summary' => 'Resumen ejecutivo.',
            'documented_results' => [['title' => 'Linea comercial', 'paragraphs' => ['Resultado confirmado.']]],
            'effort_analysis' => 'Se registraron actividades.',
            'interpretation_notes' => ['Los puntos de historia no equivalen a horas.'],
            'closing' => ['Cierre prudente.'],
            'activity_summaries' => [['issue_key' => 'CLI-1', 'result_summary' => 'Se confirmo el ajuste.']],
        ], $criteria, $snapshot);

        $this->assertSame(2, $snapshot['client_report']['total_records']);
        $this->assertSame(9.0, $snapshot['client_report']['total_effort']);
        $this->assertSame(['Historia' => 1, 'Tarea' => 1], $snapshot['client_report']['records_by_type']);
        $this->assertSame(1, count($normalized['documented_results']));
        $this->assertSame('client_report', $normalized['format']);
        $this->assertCount(2, $normalized['activity_summaries']);
        $this->assertArrayHasKey('effort', $normalized['activity_summaries'][0]);
        $this->assertArrayNotHasKey('story_points', $normalized['activity_summaries'][0]);
        $this->assertStringNotContainsString('Story Points', json_encode($normalized, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('horas', strtolower(json_encode($normalized, JSON_UNESCAPED_UNICODE)));
        $this->assertSame('Se confirmo el ajuste.', $normalized['activity_summaries'][0]['result_summary']);
        $this->assertStringContainsString('Resultados documentados', $service->prompt($criteria, $snapshot));
        $this->assertSame('jira_client_report', $service->schema('client_report')['name']);
    }

    public function test_client_report_pdf_view_uses_legacy_sections_with_erp_layout(): void
    {
        $report = new \App\Models\jira_report([
            'title' => 'Informe cliente',
            'intention' => 'client_report',
            'from_date' => now()->subDays(7),
            'to_date' => now(),
            'generated_at' => now(),
            'data_sources' => ['story_points'],
        ]);
        $snapshot = [
            'summary' => ['issue_count' => 1, 'story_points' => 2, 'worklog_hours' => null],
            'client_report' => [
                'total_records' => 1,
                'total_effort' => 2,
                'unestimated_records' => 0,
                'records_by_type' => ['Historia' => 1],
                'effort_by_type' => ['Historia' => 2],
            ],
            'report' => ['filters' => []],
        ];
        $content = [
            'format' => 'client_report',
            'report_title' => 'Informe cliente',
            'executive_summary' => 'Resumen.',
            'documented_results' => [['title' => 'Linea', 'paragraphs' => ['Resultado.']]],
            'effort_analysis' => 'Esfuerzo.',
            'interpretation_notes' => ['Nota.'],
            'closing' => ['Cierre.'],
            'activity_summaries' => [[
                'issue_key' => 'CLI-1',
                'summary' => 'Actividad',
                'issue_type' => 'Historia',
                'priority' => 'Media',
                'effort' => 2,
                'result_summary' => 'Resultado de actividad.',
            ]],
        ];

        $html = view('pdf.jira_report', ['Data' => compact('report', 'snapshot', 'content')])->render();

        $this->assertStringContainsString('Resultados documentados', $html);
        $this->assertStringContainsString('Detalle de actividades documentadas', $html);
        $this->assertStringNotContainsString('Indicadores de gestion', $html);
        $this->assertStringContainsString('Informe de resultados', $html);
        $this->assertStringNotContainsString('Informe de gestion - Jira', $html);
    }

    public function test_jira_email_view_uses_the_erp_corporate_layout(): void
    {
        $html = view('mail.reports.jira', ['Data' => [
            'report_title' => 'Reporte operativo - 12/09/2026',
            'period' => '01/09/2026 - 12/09/2026',
            'generated_at' => '12/09/2026 00:10',
        ]])->render();

        $this->assertStringContainsString('email-container', $html);
        $this->assertStringContainsString('Tu reporte de resultados ha sido generado', $html);
        $this->assertStringContainsString('Detalle del reporte', $html);
        $this->assertStringContainsString('Reporte operativo - 12/09/2026', $html);
        $this->assertStringNotContainsString('<p>Hola,</p>', $html);
    }

    public function test_report_response_parser_accepts_structured_and_wrapped_json(): void
    {
        $service = app(jira_report_service::class);
        $payload = ['report_title' => 'Reporte cliente', 'executive_summary' => 'Resumen'];

        $this->assertSame($payload, $service->parseResponse(['data' => [$payload]]));
        $this->assertSame($payload, $service->parseResponse(['data' => ['```json\n'.json_encode($payload).'\n```']]));
        $this->assertSame($payload, $service->parseResponse(['data' => ['Respuesta: '.json_encode($payload)]]));
        $this->assertSame($payload, $service->parseResponse(['data' => [json_encode(json_encode($payload))]]));
        $this->assertSame($payload, $service->parseResponse(['output_text' => json_encode($payload)]));
    }

    public function test_web_sync_continues_across_small_batches(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira por lotes',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $searchCalls = 0;
        Http::fake(function ($request) use (&$searchCalls) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            return match (true) {
                str_ends_with($path, '/field') => Http::response([
                    ['id' => 'customfield_10016', 'name' => 'Story Points'],
                ]),
                str_ends_with($path, '/project/search') => Http::response([
                    'isLast' => true,
                    'values' => [['id' => '10001', 'key' => 'OPS', 'name' => 'Operacion']],
                ]),
                str_ends_with($path, '/search/jql') => (++$searchCalls === 1)
                    ? Http::response([
                        'isLast' => false,
                        'nextPageToken' => 'page-2',
                        'issues' => [[
                            'id' => '20001',
                            'key' => 'OPS-1',
                            'fields' => [
                                'project' => ['key' => 'OPS'],
                                'issuetype' => ['name' => 'Task'],
                                'summary' => 'Primera tarea',
                                'status' => ['name' => 'Done', 'statusCategory' => ['name' => 'Done']],
                                'resolutiondate' => '2026-09-05T10:00:00.000+0000',
                            ],
                        ]],
                    ])
                    : Http::response([
                        'isLast' => true,
                        'issues' => [[
                            'id' => '20002',
                            'key' => 'OPS-2',
                            'fields' => [
                                'project' => ['key' => 'OPS'],
                                'issuetype' => ['name' => 'Task'],
                                'summary' => 'Segunda tarea',
                                'status' => ['name' => 'Done', 'statusCategory' => ['name' => 'Done']],
                                'resolutiondate' => '2026-09-05T10:00:00.000+0000',
                            ],
                        ]],
                    ]),
                str_contains($path, '/worklog') => Http::response(['total' => 0, 'worklogs' => []]),
                str_contains($path, '/changelog') => Http::response(['total' => 0, 'values' => []]),
                default => Http::response([], 200),
            };
        });

        $first = app(jira_sync_service::class)->syncBatch($connection, 1, 1, 0, null, null, null, 'full');
        $second = app(jira_sync_service::class)->syncBatch(
            $connection->fresh(),
            1,
            1,
            $first['next_start_at'],
            $first['next_page_token'],
            $first['from'],
            $first['to'],
            'full',
        );

        $this->assertTrue($first['has_more']);
        $this->assertFalse($second['has_more']);
        $this->assertDatabaseCount('jira_issues', 2);
        $this->assertNotNull($connection->fresh()->last_sync_at);
    }

    public function test_incremental_sync_uses_the_last_successful_sync_as_cursor(): void
    {
        $lastSyncAt = now()->subHour()->startOfMinute();
        $connection = jira_connection::create([
            'name' => 'Jira incremental',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
            'last_sync_at' => $lastSyncAt,
        ]);
        $capturedJql = null;
        Http::fake(function ($request) use (&$capturedJql) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/search/jql')) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $capturedJql = $query['jql'] ?? null;
                return Http::response(['isLast' => true, 'total' => 0, 'issues' => []]);
            }
            if (str_ends_with($path, '/field')) {
                return Http::response([]);
            }
            if (str_ends_with($path, '/project/search')) {
                return Http::response(['isLast' => true, 'values' => []]);
            }

            return Http::response([], 200);
        });

        $result = app(jira_sync_service::class)->sync($connection, 1, false, true);

        $expectedFrom = $lastSyncAt->copy()->subMinutes(2)->format('Y-m-d H:i');
        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('updated >= "'.$expectedFrom, (string) $capturedJql);
        $this->assertStringContainsString('ORDER BY updated ASC', (string) $capturedJql);
        $this->assertNotNull($connection->fresh()->last_sync_at);
    }

    public function test_full_sync_starts_at_the_beginning_of_jira_history(): void
    {
        $connection = jira_connection::create([
            'name' => 'Jira completa',
            'site_url' => 'https://demo.atlassian.net',
            'provider' => 'jira_cloud',
            'status' => 'active',
            'credentials' => ['email' => 'robot@example.test', 'api_token' => 'secret-token'],
        ]);
        $project = $connection->projects()->create([
            'external_id' => '10001',
            'project_key' => 'OPS',
            'name' => 'Operacion',
            'status' => 'active',
        ]);
        $project->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '20001',
            'issue_key' => 'OPS-1',
            'issue_type' => 'Story',
            'summary' => 'Ultima historia local',
            'jira_created_at' => '2026-09-01 10:00:00',
        ]);
        $capturedJql = null;
        Http::fake(function ($request) use (&$capturedJql) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/search/jql')) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $capturedJql = $query['jql'] ?? null;
                return Http::response(['isLast' => true, 'issues' => []]);
            }
            if (str_ends_with($path, '/field')) {
                return Http::response([]);
            }
            if (str_ends_with($path, '/project/search')) {
                return Http::response(['isLast' => true, 'values' => []]);
            }

            return Http::response([], 200);
        });

        $result = app(jira_sync_service::class)->syncBatch($connection, 1, 10, 0, null, null, null, 'full');

        $this->assertFalse($result['has_more']);
        $this->assertStringContainsString('created >= "1970-01-01 00:00', (string) $capturedJql);
        $this->assertStringContainsString('ORDER BY created ASC', (string) $capturedJql);
    }
}
