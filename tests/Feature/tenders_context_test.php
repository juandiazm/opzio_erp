<?php

namespace Tests\Feature;

use App\Models\tenders_opportunity;
use App\Models\tenders_pipeline_item;
use App\Models\tenders_document;
use App\Services\Tenders\tenders_context_service;
use App\Services\Tenders\tenders_document_service;
use App\Services\Tenders\tenders_secop_client;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class tenders_context_test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        foreach ([
            '2023_10_03_025645_create_users_table.php',
            '2026_09_03_000002_create_tenders_contexts_table.php',
            '2026_09_04_000004_seed_opzio_manifesto_tenders_context.php',
            '2026_09_19_000001_create_tenders_connections_table.php',
            '2026_09_19_000002_create_tenders_opportunities_table.php',
            '2026_09_19_000003_create_tenders_pipeline_tables.php',
        ] as $migration) {
            Artisan::call('migrate', [
                '--path' => database_path('migrations/'.$migration),
                '--realpath' => true,
            ]);
        }
        DB::table('users')->insert([
            'id' => 7,
            'unique_id' => 'test-user-7',
            'name' => 'Usuario',
            'lastname' => 'Prueba',
            'username' => 'usuario.prueba',
            'email' => 'usuario.prueba@example.test',
            'identification' => 'TEST-7',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
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
                ->assertSee('Configuracion')
            ->assertSee('<option value="undefined" selected>Sin definir</option>', false)
            ->assertSee('OPZIO S.A.S.');
    }

    public function test_pipeline_route_forwards_filters_and_returns_applications()
    {
        $opportunity = tenders_opportunity::create([
            'source' => 'secop2',
            'source_id' => 'fixture-001',
            'title' => 'Proceso de prueba',
            'entity' => 'Entidad de prueba',
            'status' => 'open',
            'payload_hash' => 'fixture-pipeline-001',
            'raw_payload' => [],
        ]);
        tenders_pipeline_item::create([
            'tenant_id' => 'opzio',
            'tenders_opportunity_id' => $opportunity->id,
            'stage' => 'saved',
        ]);

        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->getJson('/admin/tenders/applications?stage=saved&search=prueba&page=1&per_page=5');

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.0.opportunity_id', 'secop2:fixture-001')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_context_route_persists_and_syncs_the_company_context()
    {
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
            ->assertJsonPath('data.profile_version', 3);
        $this->assertDatabaseHas('tenders_contexts', [
            'tenant_id' => 'opzio',
            'company_name' => 'OPZIO S.A.S.',
            'version' => 3,
            'updated_by' => 7,
        ], 'sqlite');
    }

    public function test_discovery_route_syncs_context_before_querying_results()
    {
        tenders_opportunity::create([
            'source' => 'secop2',
            'source_id' => 'fixture-discovery-001',
            'source_process_id' => 'CO1.BDOS.DISCOVERY-001',
            'title' => 'Servicio de desarrollo de software',
            'description' => 'Desarrollo de plataforma web',
            'entity' => 'Entidad de prueba',
            'source_status' => 'Publicado',
            'opening_status' => 'Abierto',
            'status' => 'open',
            'payload_hash' => 'fixture-discovery-001',
            'raw_payload' => [],
        ]);
        tenders_opportunity::create([
            'source' => 'secop2',
            'source_id' => 'fixture-discovery-002',
            'source_process_id' => 'CO1.BDOS.DISCOVERY-002',
            'title' => 'Servicio de soporte de software',
            'description' => 'Soporte de plataforma web',
            'entity' => 'Entidad de prueba',
            'source_status' => 'Publicado',
            'opening_status' => 'Abierto',
            'status' => 'open',
            'payload_hash' => 'fixture-discovery-002',
            'raw_payload' => [],
        ]);

        $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->withHeader('Idempotency-Key', 'fixture-discovery-feedback-001')
            ->postJson('/admin/tenders/feedback', [
                'opportunity_id' => 'secop2:fixture-discovery-002',
                'event_type' => 'interested',
            ])
            ->assertOk();

        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->postJson('/admin/tenders/discovery', [
                'page' => 1,
                'per_page' => 10,
                'status' => 'open',
                'feedback_state' => 'undefined',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.opportunity_id', 'secop2:fixture-discovery-001');
    }

    public function test_sync_route_syncs_context_and_starts_secop_refresh()
    {
        Http::fake([
            'https://www.datos.gov.co/resource/*' => Http::response([]),
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
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.sources.secop1.0.complete', true);
    }

    public function test_configuration_route_encrypts_token_and_persists_filters()
    {
        $response = $this->withoutMiddleware()
            ->withSession(['user' => ['id' => 7]])
            ->postJson('/admin/tenders/configuration/save', [
                'name' => 'SECOP QA',
                'app_token' => 'secret-token-qa',
                'secop1_enabled' => false,
                'secop2_enabled' => true,
                'min_contract_value' => 1000000,
                'max_contract_value' => 5000000,
                'excluded_terms' => 'Hardware puro, Cableado',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.has_app_token', true)
            ->assertJsonMissing(['app_token' => 'secret-token-qa']);
        $stored = (string) DB::table('tenders_connections')->value('credentials');
        $this->assertStringNotContainsString('secret-token-qa', $stored);
        $this->assertDatabaseHas('tenders_connections', ['name' => 'SECOP QA', 'status' => 'draft'], 'sqlite');
    }

    public function test_configuration_test_uses_secop_directly()
    {
        Http::fake(['https://www.datos.gov.co/resource/*' => Http::response([])]);
        $connection = app(\App\Services\Tenders\tenders_configuration_service::class)->save([
            'name' => 'SECOP test',
            'app_token' => 'test-token',
        ], 7);

        $response = $this->withoutMiddleware()->postJson('/admin/tenders/configuration/test');

        $response->assertOk()->assertJsonPath('status', 1);
        $this->assertSame('active', $connection->fresh()->status);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.datos.gov.co/resource/'));
    }

    public function test_feedback_is_idempotent_in_the_erp_database()
    {
        $opportunity = tenders_opportunity::create([
            'source' => 'secop2',
            'source_id' => 'fixture-feedback-001',
            'title' => 'Proceso de feedback',
            'entity' => 'Entidad de prueba',
            'status' => 'open',
            'payload_hash' => 'fixture-feedback-001',
            'raw_payload' => [],
        ]);
        $url = '/admin/tenders/feedback';
        $payload = ['opportunity_id' => 'secop2:'.$opportunity->source_id, 'event_type' => 'interested'];
        $first = $this->withoutMiddleware()->withSession(['user' => ['id' => 7]])->withHeader('Idempotency-Key', 'feedback-local-001')->postJson($url, $payload);
        $second = $this->withoutMiddleware()->withSession(['user' => ['id' => 7]])->withHeader('Idempotency-Key', 'feedback-local-001')->postJson($url, $payload);

        $first->assertOk()->assertJsonPath('data.feedback_id', 1);
        $second->assertOk()->assertJsonPath('data.duplicate', true);
        $this->assertDatabaseCount('tenders_feedback_events', 1, 'sqlite');
    }

    public function test_text_document_is_processed_into_private_chunks()
    {
        Storage::fake('tenders_documents');
        $opportunity = tenders_opportunity::create([
            'source' => 'secop2',
            'source_id' => 'fixture-document-001',
            'title' => 'Proceso documental',
            'entity' => 'Entidad de prueba',
            'status' => 'open',
            'payload_hash' => 'fixture-document-001',
            'raw_payload' => [],
        ]);
        $document = tenders_document::create([
            'tenders_opportunity_id' => $opportunity->id,
            'source' => 'secop2',
            'source_document_id' => 'doc-local-001',
            'filename' => 'requisitos.txt',
            'extension' => 'txt',
            'source_url' => 'https://community.secop.gov.co/Public/Archive/doc-local-001',
            'status' => 'discovered',
        ]);
        Http::fake(['https://community.secop.gov.co/*' => Http::response('Experiencia demostrable en desarrollo de software.', 200)]);

        $result = app(tenders_document_service::class)->process($document);

        $this->assertSame('processed', $result['status']);
        $this->assertDatabaseHas('tenders_documents', ['id' => $document->id, 'status' => 'processed'], 'sqlite');
        $this->assertDatabaseCount('tenders_document_chunks', 1, 'sqlite');
        Storage::disk('tenders_documents')->assertExists('secop2/doc-local-001.txt');
    }
}