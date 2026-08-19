<?php

namespace Tests\Feature;

use App\Domain\Servers\Models\servers_agent;
use App\Domain\Servers\Models\servers_host;
use App\Domain\Servers\Models\servers_project;
use App\Models\client;
use App\Models\license;
use App\Models\license_notification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class servers_notifications_test extends TestCase
{
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.servers.token' => 'test-observer-token',
            'services.servers.loopback_only' => true,
            'services.servers.max_payload_bytes' => 10485760,
        ]);
        DB::purge('sqlite');

        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_14_000000_create_servers_tables.php'),
            '--realpath' => true,
        ]);

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->boolean('active')->default(true);
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('license_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('license_id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_18_000005_add_notifications_to_servers_projects_table.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_18_000006_add_notification_initialization_to_servers_projects_table.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_19_000007_backfill_initialized_server_project_notifications.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_19_000008_normalize_server_project_notification_sources.php'),
            '--realpath' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_08_19_000009_add_notification_name_to_servers_projects_table.php'),
            '--realpath' => true,
        ]);

        $host = servers_host::create([
            'key' => 'test-host',
            'name' => 'Test host',
            'hostname' => 'test-host.local',
            'environment' => 'testing',
            'enabled' => true,
        ]);
        $this->project = servers_project::create([
            'host_id' => $host->id,
            'key' => 'test-project',
            'name' => 'Test project',
            'path' => '/var/www/test-project',
            'environment' => 'testing',
            'enabled' => true,
            'notifications_enabled' => false,
        ]);
    }

    public function test_project_recipients_include_client_data_and_active_license_notifiers_only()
    {
        $client = client::create([
            'name' => 'Cliente Uno',
            'lastname' => 'Principal',
            'email' => 'cliente@example.test',
            'phone' => '3000000000',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia activa',
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'licencia@example.test',
            'phone' => '3110000000',
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'ignorado@example.test',
            'phone' => '3120000000',
            'active' => false,
        ]);
        $inactiveLicense = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia inactiva',
            'active' => false,
        ]);
        license_notification::forceCreate([
            'license_id' => $inactiveLicense->id,
            'email' => 'licencia-inactiva@example.test',
            'phone' => '3130000000',
            'active' => true,
        ]);

        $response = $this->withoutMiddleware()->postJson('/admin/servers/project-config/recipients', [
            'client_id' => $client->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(4, 'data.recipients')
            ->assertJsonPath('data.client.complete_name', 'Cliente Uno Principal');
        $this->assertSame(
            [],
            collect($response->json('data.recipients'))->filter(function ($recipient) {
                return str_contains($recipient['value'], 'ignorado')
                    || str_contains($recipient['value'], 'inactiva');
            })->values()->all()
        );
    }

    public function test_project_configuration_saves_selected_recipients_and_can_enable_notifications()
    {
        $client = client::create([
            'name' => 'Cliente Dos',
            'email' => 'cliente-dos@example.test',
            'phone' => '3000000001',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia dos',
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'licencia-dos@example.test',
            'phone' => '3110000001',
            'active' => true,
        ]);

        $recipientsResponse = $this->withoutMiddleware()->postJson('/admin/servers/project-config/recipients', [
            'client_id' => $client->id,
        ]);
        $recipients = $recipientsResponse->json('data.recipients');
        $selectedKeys = collect($recipients)
            ->filter(function ($recipient) {
                return $recipient['channel'] === 'email';
            })
            ->pluck('key')
            ->values()
            ->all();

        $response = $this->withoutMiddleware()->postJson('/admin/servers/project-config/update', [
            'project_id' => $this->project->id,
            'client_id' => $client->id,
            'notifications_enabled' => true,
            'notification_name' => 'Portal de Clientes',
            'recipient_keys' => $selectedKeys,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.project.client_id', $client->id)
            ->assertJsonPath('data.project.notifications_enabled', true)
            ->assertJsonPath('data.project.notification_name', 'Portal de Clientes')
            ->assertJsonPath('data.has_recipients', true)
            ->assertJsonCount(2, 'data.selected_recipients');
        $this->assertDatabaseCount('servers_project_notifications', 2, 'sqlite');
        $this->assertDatabaseHas('servers_project_notifications', [
            'project_id' => $this->project->id,
            'source_type' => 'project',
            'source_id' => null,
            'channel' => 'email',
            'value' => 'cliente-dos@example.test',
        ], 'sqlite');
        $this->assertDatabaseHas('servers_projects', [
            'id' => $this->project->id,
            'client_id' => $client->id,
            'notifications_enabled' => 1,
            'notification_name' => 'Portal de Clientes',
        ], 'sqlite');
    }

    public function test_project_rejects_contacts_from_another_client_and_discovery_preserves_configuration()
    {
        $firstClient = client::create([
            'name' => 'Cliente Tres',
            'email' => 'cliente-tres@example.test',
            'active' => true,
        ]);
        $firstLicense = license::forceCreate([
            'client_id' => $firstClient->id,
            'name' => 'Licencia tres',
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $firstLicense->id,
            'email' => 'licencia-tres@example.test',
            'active' => true,
        ]);
        $secondClient = client::create([
            'name' => 'Cliente Cuatro',
            'email' => 'cliente-cuatro@example.test',
            'active' => true,
        ]);
        $secondLicense = license::forceCreate([
            'client_id' => $secondClient->id,
            'name' => 'Licencia cuatro',
            'active' => true,
        ]);
        $foreignNotification = license_notification::forceCreate([
            'license_id' => $secondLicense->id,
            'email' => 'licencia-cuatro@example.test',
            'active' => true,
        ]);

        $foreignKey = 'license_notification:'.$foreignNotification->id.':email';
        $invalidResponse = $this->withoutMiddleware()->postJson('/admin/servers/project-config/update', [
            'project_id' => $this->project->id,
            'client_id' => $firstClient->id,
            'notifications_enabled' => true,
            'recipient_keys' => [$foreignKey],
        ]);
        $invalidResponse->assertStatus(400);
        $this->assertDatabaseHas('servers_projects', [
            'id' => $this->project->id,
            'client_id' => null,
            'notifications_enabled' => 0,
        ], 'sqlite');

        $validRecipients = $this->withoutMiddleware()->postJson('/admin/servers/project-config/recipients', [
            'client_id' => $firstClient->id,
        ])->json('data.recipients');
        $validKey = collect($validRecipients)->firstWhere('channel', 'email')['key'];
        $this->withoutMiddleware()->postJson('/admin/servers/project-config/update', [
            'project_id' => $this->project->id,
            'client_id' => $firstClient->id,
            'notifications_enabled' => true,
            'recipient_keys' => [$validKey],
        ])->assertOk();

        $host = servers_host::first();
        $agent = servers_agent::create([
            'host_id' => $host->id,
            'agent_id' => 'test-agent',
            'enabled' => true,
        ]);
        $this->withoutMiddleware()->postJson('/api/internal/servers/v1/discovery', [
            'agent_id' => $agent->agent_id,
            'projects' => [[
                'key' => $this->project->key,
                'name' => $this->project->name,
                'path' => '/var/www/updated-test-project',
                'environment' => 'production',
                'php_version' => '8.3',
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('servers_projects', [
            'id' => $this->project->id,
            'client_id' => $firstClient->id,
            'notifications_enabled' => 1,
            'path' => '/var/www/updated-test-project',
        ], 'sqlite');
        $this->assertDatabaseCount('servers_project_notifications', 1, 'sqlite');
    }

    public function test_project_notifications_become_independent_after_initial_import_and_support_crud()
    {
        $client = client::create([
            'name' => 'Cliente CRUD',
            'email' => 'cliente-crud@example.test',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia CRUD',
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'inicial@example.test',
            'active' => true,
        ]);

        $available = $this->withoutMiddleware()->postJson('/admin/servers/project-config/recipients', [
            'client_id' => $client->id,
        ])->json('data.recipients');
        $initialKey = collect($available)->firstWhere('value', 'inicial@example.test')['key'];
        $this->withoutMiddleware()->postJson('/admin/servers/project-config/update', [
            'project_id' => $this->project->id,
            'client_id' => $client->id,
            'notifications_enabled' => true,
            'recipient_keys' => [$initialKey],
        ])->assertOk();

        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'posterior@example.test',
            'active' => true,
        ]);
        $config = $this->withoutMiddleware()->postJson('/admin/servers/project-config/get', [
            'project_id' => $this->project->id,
        ]);
        $config
            ->assertOk()
            ->assertJsonPath('data.needs_initial_import', false)
            ->assertJsonCount(0, 'data.available_recipients');
        $this->assertSame(
            [],
            collect($config->json('data.selected_recipients'))->filter(function ($recipient) {
                return $recipient['value'] === 'posterior@example.test';
            })->all()
        );

        $addResponse = $this->withoutMiddleware()->postJson('/admin/servers/project-config/notifications/add', [
            'project_id' => $this->project->id,
            'channel' => 'email',
            'value' => 'propio@example.test',
            'recipient_name' => 'Contacto propio',
        ]);
        $addResponse->assertOk();
        $ownNotification = collect($addResponse->json('data.selected_recipients'))
            ->firstWhere('value', 'propio@example.test');

        $updateResponse = $this->withoutMiddleware()->postJson('/admin/servers/project-config/notifications/update', [
            'project_id' => $this->project->id,
            'notification_id' => $ownNotification['id'],
            'channel' => 'phone',
            'value' => '3001234567',
            'recipient_name' => 'Contacto propio actualizado',
        ]);
        $updateResponse->assertOk();
        $this->assertSame(
            '3001234567',
            collect($updateResponse->json('data.selected_recipients'))->firstWhere('value', '3001234567')['value']
        );

        $initialStoredNotification = collect($updateResponse->json('data.selected_recipients'))
            ->firstWhere('value', 'inicial@example.test');
        $deleteResponse = $this->withoutMiddleware()->postJson('/admin/servers/project-config/notifications/delete', [
            'project_id' => $this->project->id,
            'notification_id' => $initialStoredNotification['id'],
        ]);
        $deleteResponse
            ->assertOk()
            ->assertJsonCount(1, 'data.selected_recipients');
        $this->assertDatabaseMissing('servers_project_notifications', [
            'id' => $initialStoredNotification['id'],
        ], 'sqlite');
    }

    public function test_legacy_project_recipients_are_marked_initialized_and_do_not_reload_licenses()
    {
        $client = client::create([
            'name' => 'Cliente legado',
            'email' => 'cliente-legado@example.test',
            'active' => true,
        ]);
        $this->project->update([
            'client_id' => $client->id,
            'notifications_enabled' => true,
            'notification_recipients_initialized' => false,
        ]);
        DB::table('servers_project_notifications')->insert([
            'project_id' => $this->project->id,
            'source_type' => 'license_notification',
            'source_id' => 901,
            'source_key' => 'license_notification:901:email',
            'channel' => 'email',
            'value' => 'legacy@example.test',
            'recipient_name' => 'Contacto legado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->postJson('/admin/servers/project-config/get', [
            'project_id' => $this->project->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.notification_recipients_initialized', true)
            ->assertJsonPath('data.needs_initial_import', false)
            ->assertJsonCount(0, 'data.available_recipients')
            ->assertJsonPath('data.selected_recipients.0.value', 'legacy@example.test')
            ->assertJsonPath('data.selected_recipients.0.source_type', 'project')
            ->assertJsonPath('data.selected_recipients.0.source_id', null);
        $this->assertDatabaseHas('servers_projects', [
            'id' => $this->project->id,
            'notification_recipients_initialized' => 1,
        ], 'sqlite');
    }

    public function test_dashboard_filters_projects_by_notification_status()
    {
        $this->project->update([
            'notifications_enabled' => true,
        ]);
        servers_project::create([
            'host_id' => $this->project->host_id,
            'key' => 'inactive-notification-project',
            'name' => 'Inactive notification project',
            'path' => '/var/www/inactive-notification-project',
            'environment' => 'testing',
            'enabled' => true,
            'notifications_enabled' => false,
        ]);

        $activeResponse = $this->withoutMiddleware()->postJson('/admin/servers/get-page', [
            'notifications' => 'active',
            'pagination' => ['page' => 1, 'per_page' => 10],
        ]);
        $activeResponse
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.key', 'test-project')
            ->assertJsonPath('data.0.notifications_enabled', true);

        $inactiveResponse = $this->withoutMiddleware()->postJson('/admin/servers/get-page', [
            'notifications' => 'inactive',
            'pagination' => ['page' => 1, 'per_page' => 10],
        ]);
        $inactiveResponse
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.key', 'inactive-notification-project')
            ->assertJsonPath('data.0.notifications_enabled', false);
    }
}
