<?php

namespace Tests\Feature;

use App\Services\Tenders\tenders_context_service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class tenders_context_test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_03_000002_create_tenders_contexts_table.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_09_04_000004_seed_opzio_manifesto_tenders_context.php'),
            '--realpath' => true,
        ]);
    }

    public function test_context_service_normalizes_lists_and_versions_context()
    {
        $service = new tenders_context_service();

        $context = $service->save('opzio', [
            'company_name' => 'OPZIO S.A.S.',
            'description' => 'Desarrollo de software',
            'services' => "Desarrollo de software, Integraciones\nDesarrollo de software",
            'technologies' => 'Python; Laravel',
            'sectors' => 'Gobierno, Educacion',
            'geography' => 'Colombia',
            'excluded_terms' => 'Hardware puro',
            'min_contract_value' => '1000000',
            'max_contract_value' => '5000000',
        ], 7);

        $this->assertSame(3, $context->version);
        $this->assertSame(['Desarrollo de software', 'Integraciones'], $context->services);
        $this->assertSame(['Python', 'Laravel'], $context->technologies);
        $this->assertSame(1000000.0, $context->min_contract_value);
        $this->assertSame(7, $context->updated_by);
    }

    public function test_manifesto_migration_seeds_the_initial_opzio_context()
    {
        $context = (new tenders_context_service())->get('opzio');

        $this->assertSame(2, $context->version);
        $this->assertStringContainsString('Mision:', $context->description);
        $this->assertStringContainsString('Vision:', $context->description);
        $this->assertStringContainsString('Valores:', $context->description);
        $this->assertContains('Desarrollo de software y plataformas SaaS', $context->services);
        $this->assertContains('Experiencias digitales', $context->technologies);
        $this->assertContains('Inteligencia y automatizacion', $context->technologies);
        $this->assertContains('Latinoamerica', $context->geography);
    }

    public function test_tenders_page_renders_the_pivot_and_context_tabs()
    {
        $response = $this->withoutMiddleware()
            ->withSession([
                'user' => [
                    'id' => 7,
                    'name' => 'Usuario',
                    'lastname' => 'Prueba',
                    'photo' => '',
                    'color' => '#000000',
                    'reset_password' => null,
                ],
                'permissions' => [],
                'app_permissions' => [],
            ])
            ->get('/admin/tenders');

        $response
            ->assertOk()
            ->assertSee('id="nav-tab"', false)
            ->assertSee('Discovery')
            ->assertSee('Contexto')
            ->assertSee('Seguimiento')
            ->assertSee('OPZIO S.A.S.');
    }

    public function test_pipeline_route_forwards_filters_and_returns_applications()
    {
        Http::fake([
            'http://127.0.0.1:9081/*' => Http::response([
                'request_id' => 'pipeline-request-004',
                'data' => [[
                    'opportunity_id' => 'secop2:fixture-001',
                    'title' => 'Proceso de prueba',
                    'stage' => 'saved',
                ]],
                'meta' => [
                    'page' => 2,
                    'per_page' => 5,
                    'total' => 1,
                    'total_pages' => 1,
                ],
            ]),
        ]);

        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->getJson('/admin/tenders/applications?stage=saved&search=prueba&page=2&per_page=5');

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.0.opportunity_id', 'secop2:fixture-001')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.total', 1);
        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && $query['stage'] === 'saved'
                && $query['search'] === 'prueba'
                && $query['page'] === '2'
                && $query['per_page'] === '5';
        });
    }

    public function test_context_route_persists_and_syncs_the_company_context()
    {
        Http::fake([
            'http://127.0.0.1:9081/*' => Http::response([
                'request_id' => 'context-request-001',
                'data' => ['tenant_id' => 'opzio', 'profile_version' => 2],
                'meta' => [],
            ]),
        ]);

        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->postJson('/admin/tenders/context', [
                'company_name' => 'OPZIO S.A.S.',
                'description' => 'Desarrollo de software e integraciones',
                'services' => 'Desarrollo de software, Integraciones',
                'technologies' => 'Python, Laravel',
                'sectors' => 'Gobierno',
                'geography' => 'Colombia, Remoto',
                'excluded_terms' => 'Hardware puro',
                'min_contract_value' => 1000000,
                'max_contract_value' => 5000000,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.profile_version', 3)
            ->assertJsonPath('request_id', 'context-request-001');
        $this->assertDatabaseHas('tenders_contexts', [
            'tenant_id' => 'opzio',
            'company_name' => 'OPZIO S.A.S.',
            'version' => 3,
            'updated_by' => 7,
        ], 'sqlite');
        Http::assertSentCount(1);
    }

    public function test_discovery_route_syncs_context_before_querying_results()
    {
        Http::fakeSequence('http://127.0.0.1:9081/*')
            ->push([
                'request_id' => 'context-request-002',
                'data' => ['tenant_id' => 'opzio', 'profile_version' => 1],
                'meta' => [],
            ])
            ->push([
                'request_id' => 'discovery-request-002',
                'generated_at' => '2026-09-03T12:00:00Z',
                'data' => [],
                'meta' => [
                    'tenant_id' => 'opzio',
                    'page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'total_pages' => 0,
                    'calculated_at' => '2026-09-03T12:00:00Z',
                ],
            ]);

        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->postJson('/admin/tenders/discovery', [
                'page' => 1,
                'per_page' => 10,
                'status' => 'open',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('request_id', 'discovery-request-002');
        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_ends_with($request->url(), '/v1/profiles/opzio');
        });
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/v1/discovery?page=1&per_page=10&status=open');
        });
    }

    public function test_sync_route_syncs_context_and_starts_secop_refresh()
    {
        Http::fakeSequence('http://127.0.0.1:9081/*')
            ->push([
                'request_id' => 'context-request-003',
                'data' => ['tenant_id' => 'opzio', 'profile_version' => 1],
                'meta' => [],
            ])
            ->push([
                'request_id' => 'sync-request-003',
                'data' => [
                    'started' => true,
                    'status' => 'queued',
                    'sources' => ['secop1', 'secop2'],
                ],
                'meta' => ['lookback_days' => 7],
            ]);

        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->postJson('/admin/tenders/sync', [
                'source' => 'all',
                'lookback_days' => 30,
                'max_pages' => 20,
                'reset_cursor' => true,
            ]);

        $response
            ->assertStatus(202)
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.started', true)
            ->assertJsonPath('request_id', 'sync-request-003');
        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://127.0.0.1:9081/v1/sync'
                && $request->data()['lookback_days'] === 30
                && $request->data()['max_pages'] === 20
                && $request->data()['reset_cursor'] === true;
        });
    }
}