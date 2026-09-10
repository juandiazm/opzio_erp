<?php

namespace Tests\Feature;

use App\Models\jira_connection;
use App\Services\Jira\jira_metrics_service;
use App\Services\Jira\jira_report_service;
use App\Services\Jira\jira_sync_service;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
            $table->timestamps();
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
        });
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('name');
            $table->timestamps();
        });
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_08_000001_create_jira_module_tables.php'),
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
                default => Http::response([], 200),
            };
        });

        $result = app(jira_sync_service::class)->sync($connection, 30);
        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('jira_projects', ['project_key' => 'OPS']);
        $this->assertDatabaseHas('jira_issues', ['issue_key' => 'OPS-1', 'story_points' => 5.5]);
        $this->assertDatabaseHas('jira_issue_worklogs', ['external_id' => '30001', 'time_spent_seconds' => 7200]);
        $this->assertDatabaseHas('jira_issue_changelogs', ['external_history_id' => '40001', 'field' => 'status']);
        $this->assertSame('active', $connection->fresh()->status);

        $metrics = app(jira_metrics_service::class)->dashboard([
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]);
        $this->assertSame(5.5, $metrics['summary']['story_points']);
        $this->assertSame(2.0, $metrics['summary']['worklog_hours']);
        $this->assertSame(1, $metrics['summary']['completed_issues']);
        $this->assertSame('Operacion', $metrics['projects'][0]['label']);

        $ongoingIssue = $connection->projects()->first()->issues()->create([
            'jira_connection_id' => $connection->id,
            'external_id' => '20002',
            'issue_key' => 'OPS-2',
            'issue_type' => 'Task',
            'summary' => 'Trabajo en curso',
            'status' => 'In Progress',
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
        $this->assertSame(4.0, $updatedMetrics['summary']['worklog_hours']);

        app(jira_sync_service::class)->sync($connection->fresh(), 30);
        $this->assertDatabaseCount('jira_projects', 1);
        $this->assertDatabaseCount('jira_issues', 2);
        $this->assertDatabaseCount('jira_issue_worklogs', 2);
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

        $first = app(jira_sync_service::class)->syncBatch($connection, 1, 1);
        $second = app(jira_sync_service::class)->syncBatch(
            $connection->fresh(),
            1,
            1,
            $first['next_start_at'],
            $first['next_page_token'],
            $first['from'],
            $first['to'],
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

    public function test_full_sync_starts_at_the_latest_story_created_in_database(): void
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
        $this->assertStringContainsString('created >= "2026-09-01 10:00', (string) $capturedJql);
        $this->assertStringContainsString('ORDER BY created ASC', (string) $capturedJql);
    }
}
