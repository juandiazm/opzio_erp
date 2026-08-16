<?php

namespace Tests\Feature;

use App\Domain\Observability\Models\observability_agent;
use App\Domain\Observability\Models\observability_host;
use App\Domain\Observability\Models\observability_project;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class observability_api_test extends TestCase
{
    private $agent;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.observability.token' => 'test-observer-token',
            'services.observability.loopback_only' => true,
            'services.observability.max_payload_bytes' => 10485760,
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_14_000000_create_observability_tables.php'),
            '--realpath' => true,
        ]);

        $host = observability_host::create([
            'key' => 'test-host',
            'name' => 'Test host',
            'hostname' => 'test-host.local',
            'environment' => 'testing',
            'config_version' => 3,
            'enabled' => true,
        ]);
        observability_project::create([
            'host_id' => $host->id,
            'key' => 'test-project',
            'name' => 'Test project',
            'path' => '/var/www/test-project',
            'environment' => 'testing',
            'enabled' => true,
            'attribution_mode' => 'pool',
        ]);
        $this->agent = observability_agent::create([
            'host_id' => $host->id,
            'agent_id' => 'test-agent',
            'enabled' => true,
        ]);
    }

    public function test_health_requires_the_observer_token()
    {
        $this->getJson('/api/internal/observability/v1/health')->assertStatus(401);
    }

    public function test_health_rejects_non_loopback_requests()
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.8'])
            ->withHeaders($this->observerHeaders())
            ->getJson('/api/internal/observability/v1/health')
            ->assertStatus(403);
    }

    public function test_payload_limit_is_enforced_without_content_length_header()
    {
        config(['services.observability.max_payload_bytes' => 10]);

        $this->withHeaders($this->observerHeaders())
            ->postJson('/api/internal/observability/v1/heartbeat', [
                'agent_id' => 'test-agent',
                'version' => 'this payload is larger than ten bytes',
            ])
            ->assertStatus(413);
    }

    public function test_health_and_config_are_available_to_a_registered_loopback_agent()
    {
        $headers = $this->observerHeaders();

        $this->withHeaders($headers)
            ->getJson('/api/internal/observability/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->withHeaders($headers)
            ->getJson('/api/internal/observability/v1/config')
            ->assertOk()
            ->assertJsonPath('version', 3)
            ->assertJsonPath('agent_id', 'test-agent')
            ->assertJsonPath('projects.0.key', 'test-project');
    }

    public function test_discovery_registers_projects_and_is_idempotent()
    {
        $project = [
            'key' => 'discovered-project',
            'name' => 'Discovered project',
            'path' => '/var/www/discovered-project',
            'environment' => 'production',
            'php_version' => '8.2',
            'fpm_status_url' => 'http://127.0.0.1:9091/__fpm_status/discovered-project',
            'nginx_access_log' => '/var/log/nginx/opzio/discovered-project.access.json',
            'attribution_mode' => 'approximate',
            'metadata' => ['discovery_source' => 'filesystem'],
        ];

        $firstResponse = $this->withHeaders($this->observerHeaders())->postJson(
            '/api/internal/observability/v1/discovery',
            [
                'agent_id' => 'test-agent',
                'discovered_at' => '2026-08-16T15:00:00Z',
                'projects' => [$project],
            ]
        );

        $firstResponse
            ->assertOk()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('updated', 0)
            ->assertJsonPath('version', 4);
        $this->assertDatabaseHas('observability_projects', [
            'host_id' => $this->agent->host_id,
            'key' => 'discovered-project',
            'path' => '/var/www/discovered-project',
            'fpm_status_url' => 'http://127.0.0.1:9091/__fpm_status/discovered-project',
        ], 'sqlite');

        $secondResponse = $this->withHeaders($this->observerHeaders())->postJson(
            '/api/internal/observability/v1/discovery',
            [
                'agent_id' => 'test-agent',
                'discovered_at' => '2026-08-16T15:01:00Z',
                'projects' => [$project],
            ]
        );

        $secondResponse
            ->assertOk()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('updated', 0)
            ->assertJsonPath('version', 4);
    }

    public function test_ingest_is_idempotent_by_batch_id()
    {
        $payload = [
            'agent_id' => 'test-agent',
            'batch_id' => 'batch-001',
            'captured_at' => '2026-08-14T14:00:15Z',
            'config_version' => 3,
            'host' => [
                'cpu_percent' => 12.5,
                'memory_total_bytes' => 1000000,
                'memory_available_bytes' => 500000,
            ],
            'projects' => [[
                'key' => 'test-project',
                'attribution_mode' => 'pool',
                'process_count' => 4,
                'memory_rss_bytes' => 200000,
            ]],
            'http_buckets' => [[
                'project_key' => 'test-project',
                'bucket_start' => '2026-08-14T14:00:00Z',
                'requests_total' => 10,
                'status_2xx' => 9,
                'status_5xx' => 1,
                'p95_ms' => 42.5,
            ]],
        ];

        $firstResponse = $this->withHeaders($this->observerHeaders())->postJson(
            '/api/internal/observability/v1/ingest',
            $payload
        );
        $firstResponse->assertStatus(202)->assertJsonPath('duplicate', false);

        $secondResponse = $this->withHeaders($this->observerHeaders())->postJson(
            '/api/internal/observability/v1/ingest',
            $payload
        );
        $secondResponse->assertStatus(202)->assertJsonPath('duplicate', true);

        $this->assertSame(1, DB::table('observability_ingest_batches')->count());
        $this->assertSame(1, DB::table('observability_host_samples')->count());
        $this->assertSame(1, DB::table('observability_project_samples')->count());
        $this->assertSame(1, DB::table('observability_http_buckets')->count());
    }

    public function test_ingest_rejects_projects_outside_the_active_registry()
    {
        $this->withHeaders($this->observerHeaders())
            ->postJson('/api/internal/observability/v1/ingest', [
                'agent_id' => 'test-agent',
                'batch_id' => 'batch-unknown-project',
                'captured_at' => '2026-08-14T14:00:15Z',
                'projects' => [['key' => 'not-registered']],
            ])
            ->assertStatus(422)
            ->assertJsonPath('projects.0', 'not-registered');
    }

    public function test_heartbeat_updates_agent_health()
    {
        $this->withHeaders($this->observerHeaders())
            ->postJson('/api/internal/observability/v1/heartbeat', [
                'agent_id' => 'test-agent',
                'version' => '0.1.0',
                'commit_sha' => 'abc123',
                'config_version' => 3,
                'spool_bytes' => 128,
                'spool_batches' => 2,
                'uptime_seconds' => 60,
                'collection_errors' => ['nginx' => 1],
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('observability_agents', [
            'agent_id' => 'test-agent',
            'version' => '0.1.0',
            'commit_sha' => 'abc123',
            'spool_bytes' => 128,
            'spool_batches' => 2,
        ], 'sqlite');
    }

    public function test_dashboard_lists_filtered_project_metrics_with_pagination()
    {
        $firstSampleAt = now()->subMinutes(5)->toDateTimeString();
        $lastSampleAt = now()->subMinute()->toDateTimeString();
        DB::table('observability_agents')->where('agent_id', 'test-agent')->update([
            'last_seen_at' => $lastSampleAt,
            'version' => '0.2.0',
        ]);
        DB::table('observability_project_samples')->insert([
            [
                'project_id' => 1,
                'agent_id' => 'test-agent',
                'sampled_at' => $firstSampleAt,
                'attribution_mode' => 'pool',
                'cpu_percent' => 10,
                'memory_rss_bytes' => 1000,
                'process_count' => 4,
                'fpm_active_processes' => 2,
                'fpm_idle_processes' => 6,
                'fpm_listen_queue' => 1,
                'fpm_max_listen_queue' => 10,
                'fpm_max_children_reached' => 3,
                'fpm_slow_requests' => 1,
                'storage_total_bytes' => 1000,
            ],
            [
                'project_id' => 1,
                'agent_id' => 'test-agent',
                'sampled_at' => $lastSampleAt,
                'attribution_mode' => 'pool',
                'cpu_percent' => 25,
                'memory_rss_bytes' => 2000,
                'process_count' => 5,
                'fpm_active_processes' => 3,
                'fpm_idle_processes' => 7,
                'fpm_listen_queue' => 2,
                'fpm_max_listen_queue' => 10,
                'fpm_max_children_reached' => 8,
                'fpm_slow_requests' => 4,
                'storage_total_bytes' => 1200,
            ],
        ]);
        DB::table('observability_http_buckets')->insert([
            'project_id' => 1,
            'agent_id' => 'test-agent',
            'bucket_start' => $lastSampleAt,
            'bucket_seconds' => 60,
            'requests_total' => 100,
            'status_2xx' => 95,
            'status_3xx' => 1,
            'status_4xx' => 4,
            'status_5xx' => 1,
            'status_499' => 1,
            'status_500' => 0,
            'status_502' => 1,
            'status_503' => 0,
            'status_504' => 0,
            'request_bytes' => 5000,
            'response_bytes' => 10000,
            'latency_count' => 100,
            'latency_sum_ms' => 2000,
            'p50_ms' => 12,
            'p95_ms' => 30,
            'p99_ms' => 45,
        ]);

        $response = $this->withoutMiddleware()->postJson('/admin/observability/get-page', [
            'minutes' => 60,
            'health' => 'reporting',
            'search' => 'test-project',
            'pagination' => [
                'page' => 1,
                'per_page' => 5,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.key', 'test-project')
            ->assertJsonPath('data.0.requests_total', 100)
            ->assertJsonPath('data.0.availability_percent', 99)
            ->assertJsonPath('data.0.latency_average_ms', 20)
            ->assertJsonPath('data.0.fpm_max_children_reached_delta', 5)
            ->assertJsonPath('data.0.storage_growth_bytes', 200);

        for ($index = 1; $index <= 5; $index++) {
            observability_project::create([
                'host_id' => $this->agent->host_id,
                'key' => 'empty-project-' . $index,
                'name' => 'Empty project ' . $index,
                'path' => '/var/www/empty-project-' . $index,
                'environment' => 'testing',
                'enabled' => true,
            ]);
        }
        $secondPageResponse = $this->withoutMiddleware()->postJson('/admin/observability/get-page', [
            'minutes' => 60,
            'pagination' => [
                'page' => 2,
                'per_page' => 5,
            ],
        ]);
        $secondPageResponse
            ->assertOk()
            ->assertJsonPath('pagination.total', 6)
            ->assertJsonPath('pagination.page', 2)
            ->assertJsonPath('data.0.key', 'empty-project-1');

        $exportResponse = $this->withoutMiddleware()->get('/admin/observability/export?minutes=60&search=test-project');

        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $exportResponse->headers->get('Content-Type')
        );
    }

    public function test_dashboard_sorts_by_name_and_selected_metric_before_pagination()
    {
        $alphaProject = observability_project::create([
            'host_id' => $this->agent->host_id,
            'key' => 'alpha-project',
            'name' => 'Alpha project',
            'path' => '/var/www/alpha-project',
            'environment' => 'testing',
            'enabled' => true,
        ]);
        $zuluProject = observability_project::create([
            'host_id' => $this->agent->host_id,
            'key' => 'zulu-project',
            'name' => 'Zulu project',
            'path' => '/var/www/zulu-project',
            'environment' => 'testing',
            'enabled' => true,
        ]);
        foreach (['Beta project', 'Gamma project', 'Omega project'] as $projectName) {
            observability_project::create([
                'host_id' => $this->agent->host_id,
                'key' => strtolower(str_replace(' ', '-', $projectName)),
                'name' => $projectName,
                'path' => '/var/www/' . strtolower(str_replace(' ', '-', $projectName)),
                'environment' => 'testing',
                'enabled' => true,
            ]);
        }
        $bucketStart = now()->subMinutes(5)->toDateTimeString();
        DB::table('observability_http_buckets')->insert([
            [
                'project_id' => $alphaProject->id,
                'agent_id' => 'test-agent',
                'bucket_start' => $bucketStart,
                'requests_total' => 10,
            ],
            [
                'project_id' => $zuluProject->id,
                'agent_id' => 'test-agent',
                'bucket_start' => $bucketStart,
                'requests_total' => 100,
            ],
        ]);

        $defaultResponse = $this->withoutMiddleware()->postJson('/admin/observability/get-page', [
            'minutes' => 60,
            'pagination' => [
                'page' => 1,
                'per_page' => 5,
            ],
        ]);
        $defaultResponse
            ->assertOk()
            ->assertJsonPath('sort_by', 'name')
            ->assertJsonPath('sort_direction', 'desc')
            ->assertJsonPath('data.0.key', 'zulu-project');

        $secondPageResponse = $this->withoutMiddleware()->postJson('/admin/observability/get-page', [
            'minutes' => 60,
            'pagination' => [
                'page' => 2,
                'per_page' => 5,
            ],
        ]);
        $secondPageResponse->assertJsonPath('data.0.key', 'alpha-project');

        $nameAscendingResponse = $this->withoutMiddleware()->postJson('/admin/observability/get-page', [
            'minutes' => 60,
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]);
        $nameAscendingResponse->assertJsonPath('data.0.key', 'alpha-project');

        $trafficResponse = $this->withoutMiddleware()->postJson('/admin/observability/get-page', [
            'minutes' => 60,
            'sort_by' => 'requests_per_minute',
            'sort_direction' => 'desc',
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]);
        $trafficResponse->assertJsonPath('data.0.key', 'zulu-project');
    }

    public function test_registry_commands_create_host_agent_and_project()
    {
            $this->assertSame(0, Artisan::call('observability:host', [
                'key' => 'cli-host',
                'name' => 'CLI host',
                '--hostname' => 'cli-host.local',
            ]));
            $this->assertSame(0, Artisan::call('observability:agent', [
                'agent_id' => 'cli-agent',
                'host_key' => 'cli-host',
            ]));
            $this->assertSame(0, Artisan::call('observability:project', [
                'host_key' => 'cli-host',
                'key' => 'cli-project',
                'name' => 'CLI project',
                'path' => '/var/www/cli-project',
                '--fpm-pool' => 'cli_pool',
                '--attribution-mode' => 'pool',
            ]));

            $this->assertDatabaseHas('observability_hosts', ['key' => 'cli-host'], 'sqlite');
            $this->assertDatabaseHas('observability_agents', ['agent_id' => 'cli-agent'], 'sqlite');
            $this->assertDatabaseHas('observability_projects', [
                'key' => 'cli-project',
                'fpm_pool' => 'cli_pool',
                'attribution_mode' => 'pool',
            ], 'sqlite');
        }

    private function observerHeaders(): array
    {
        return [
            'X-Opzio-Observer-Token' => 'test-observer-token',
            'X-Opzio-Observer-Agent' => 'test-agent',
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
