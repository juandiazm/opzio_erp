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
