<?php

namespace Tests\Feature;

use App\Services\Tenders\tenders_ai_client;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class tenders_module_test extends TestCase
{
    public function test_tenders_client_exposes_detail_feedback_pipeline_and_sync_status()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
            'services.tenders_ai.timeout' => 2,
        ]);

        Http::fake([
            'http://127.0.0.1:9081/*' => Http::response([
                'request_id' => 'tenders-request-001',
                'data' => [],
                'meta' => [],
            ]),
        ]);

        $client = new tenders_ai_client();
        $detail = $client->opportunity('secop2:fixture-001');
        $feedback = $client->feedback(
            'secop2:fixture-001',
            ['event_type' => 'interested'],
            'feedback-test-001'
        );
        $pipeline = $client->pipeline('secop2:fixture-001', ['stage' => 'saved']);
        $applications = $client->applications('saved');
        $syncRuns = $client->sync_runs('secop2', 5);
        $sync = $client->sync();

        $this->assertSame(1, $detail['status']);
        $this->assertSame(1, $feedback['status']);
        $this->assertSame(1, $pipeline['status']);
        $this->assertSame(1, $applications['status']);
        $this->assertSame(1, $syncRuns['status']);
        $this->assertSame(1, $sync['status']);
        Http::assertSentCount(6);
        Http::assertSent(function ($request) {
            return ($request->header('Idempotency-Key')[0] ?? null) === 'feedback-test-001';
        });
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://127.0.0.1:9081/v1/sync';
        });
    }

    public function test_tenders_client_preserves_direct_opportunity_detail()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
        ]);

        Http::fake([
            'http://127.0.0.1:9081/v1/opportunities/*' => Http::response([
                'opportunity_id' => 'secop2:fixture-001',
                'title' => 'Proceso de prueba',
                'description' => 'Detalle disponible en SECOP.',
            ]),
        ]);

        $response = (new tenders_ai_client())->opportunity('secop2:fixture-001');

        $this->assertSame(1, $response['status']);
        $this->assertSame('secop2:fixture-001', $response['data']['opportunity_id']);
        $this->assertSame('Detalle disponible en SECOP.', $response['data']['description']);
    }

    public function test_tenders_client_exposes_pipeline_history_crud()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
        ]);

        Http::fake(function ($request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'request_id' => 'history-request-001',
                    'data' => [['id' => 12, 'stage' => 'saved']],
                    'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1, 'total_pages' => 1],
                ]);
            }
            if ($request->method() === 'PATCH') {
                return Http::response([
                    'request_id' => 'history-request-002',
                    'data' => ['id' => 12, 'stage' => 'preparing'],
                    'error' => null,
                ]);
            }

            return Http::response([
                'request_id' => 'history-request-003',
                'data' => ['id' => 12, 'deleted' => true],
                'error' => null,
            ]);
        });

        $client = new tenders_ai_client();
        $history = $client->pipeline_history('secop2:fixture-001', 2, 10);
        $updated = $client->update_pipeline_entry(
            'secop2:fixture-001',
            12,
            ['stage' => 'preparing', 'notes' => 'Preparar propuesta']
        );
        $deleted = $client->delete_pipeline_entry('secop2:fixture-001', 12);

        $this->assertSame(1, $history['status']);
        $this->assertSame(1, $updated['status']);
        $this->assertSame(1, $deleted['status']);
        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_ends_with($request->url(), '/v1/opportunities/secop2%3Afixture-001/pipeline?page=2&per_page=10')
                && $query === ['page' => '2', 'per_page' => '10'];
        });
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && str_ends_with($request->url(), '/v1/opportunities/secop2%3Afixture-001/pipeline/12')
                && $request->data()['stage'] === 'preparing';
        });
        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_ends_with($request->url(), '/v1/opportunities/secop2%3Afixture-001/pipeline/12');
        });
    }

    public function test_tenders_client_saves_an_opportunity_without_creating_a_follow_up()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
        ]);

        Http::fake([
            'http://127.0.0.1:9081/v1/opportunities/*/save' => Http::response([
                'request_id' => 'save-opportunity-001',
                'data' => ['opportunity_id' => 'secop2:fixture-001', 'stage' => 'saved'],
                'meta' => ['created' => true],
                'error' => null,
            ]),
        ]);

        $response = (new tenders_ai_client())->save_opportunity('secop2:fixture-001');

        $this->assertSame(1, $response['status']);
        $this->assertTrue($response['meta']['created']);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://127.0.0.1:9081/v1/opportunities/secop2%3Afixture-001/save';
        });
    }

    public function test_tenders_client_passes_pipeline_filters_and_pagination()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
        ]);

        Http::fake([
            'http://127.0.0.1:9081/*' => Http::response([
                'request_id' => 'pipeline-request-001',
                'data' => [],
                'meta' => [
                    'page' => 2,
                    'per_page' => 25,
                    'total' => 0,
                    'total_pages' => 0,
                ],
            ]),
        ]);

        $response = (new tenders_ai_client())->applications('saved', 'plataforma', 2, 25);

        $this->assertSame(1, $response['status']);
        $this->assertSame('pipeline-request-001', $response['request_id']);
        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && $query === [
                    'stage' => 'saved',
                    'search' => 'plataforma',
                    'page' => '2',
                    'per_page' => '25',
                ];
        });
    }

    public function test_tenders_client_syncs_the_company_context()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
            'services.tenders_ai.timeout' => 2,
        ]);

        Http::fake([
            'http://127.0.0.1:9081/*' => Http::response([
                'request_id' => 'profile-request-001',
                'data' => ['tenant_id' => 'opzio', 'profile_version' => 2],
                'meta' => [],
            ]),
        ]);

        $response = (new tenders_ai_client())->sync_context([
            'company_name' => 'OPZIO S.A.S.',
            'description' => 'Desarrollo de software e integraciones',
            'services' => ['Desarrollo de software'],
            'profile_version' => 2,
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame('profile-request-001', $response['request_id']);
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'http://127.0.0.1:9081/v1/profiles/opzio'
                && $request->header('X-Opzio-Secop-Token')[0] === 'test-secops-token'
                && $request->data()['company_name'] === 'OPZIO S.A.S.'
                && $request->data()['profile_version'] === 2;
        });
    }

    public function test_tenders_client_adapts_the_discovery_contract()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => 'test-secops-token',
            'services.tenders_ai.tenant_id' => 'opzio',
            'services.tenders_ai.timeout' => 2,
        ]);

        Http::fake([
            'http://127.0.0.1:9081/*' => Http::response([
                'request_id' => 'python-request-001',
                'generated_at' => '2026-09-03T12:00:00Z',
                'data' => [[
                    'opportunity_id' => 'secop2:fixture-001',
                    'fit_score' => 82,
                ]],
                'meta' => [
                    'tenant_id' => 'opzio',
                    'calculated_at' => '2026-09-03T12:00:00Z',
                ],
            ]),
        ]);

        $response = (new tenders_ai_client())->discovery([
            'page' => 2,
            'per_page' => 10,
            'search' => 'plataforma',
            'status' => 'open',
            'feedback_state' => 'undefined',
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame('python-request-001', $response['request_id']);
        $this->assertSame('opzio', $response['meta']['tenant_id']);
        $this->assertSame(82, $response['data'][0]['fit_score']);
        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->url() === 'http://127.0.0.1:9081/v1/discovery?page=2&per_page=10&search=plataforma&status=open&feedback_state=undefined'
                && $request->header('X-Opzio-Secop-Token')[0] === 'test-secops-token'
                && $request->header('X-Opzio-Tenant-Id')[0] === 'opzio'
                && $query['page'] === '2'
                && $query['per_page'] === '10'
                && $query['feedback_state'] === 'undefined';
        });
    }

    public function test_tenders_client_fails_closed_when_not_configured()
    {
        config([
            'services.tenders_ai.base_url' => 'http://127.0.0.1:9081',
            'services.tenders_ai.token' => '',
        ]);

        $response = (new tenders_ai_client())->discovery();

        $this->assertSame(0, $response['status']);
        $this->assertSame(503, $response['http_status']);
        $this->assertSame([], $response['data']);
    }
}