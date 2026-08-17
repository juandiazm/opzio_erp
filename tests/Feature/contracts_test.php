<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\contract;
use App\Models\contract_schedule;
use App\Models\contract_template;
use App\Models\contract_type;
use App\Models\income;
use App\Models\license;
use App\traits\contracts_trait;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class contracts_test extends TestCase
{
    use contracts_trait;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(1);
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('work_email')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('state')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->string('name');
            $table->bigInteger('budget')->nullable();
            $table->unsignedBigInteger('director_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('name');
            $table->bigInteger('value')->default(0);
            $table->tinyInteger('type')->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_identification')->nullable();
            $table->string('client_name')->nullable();
            $table->date('timely_payment')->nullable();
            $table->date('cutoff_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total', 20, 2)->default(0);
            $table->tinyInteger('state')->default(0);
            $table->tinyInteger('payment_state')->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $migrations = [
            '2026_08_17_000000_create_contract_types_table.php',
            '2026_08_17_000001_create_contract_templates_table.php',
            '2026_08_17_000002_create_contracts_table.php',
            '2026_08_17_000003_create_contract_schedules_table.php',
            '2026_08_17_000005_add_schedule_foreign_key_to_contracts_table.php',
            '2026_08_17_000006_add_variables_to_contract_templates_table.php',
        ];
        foreach ($migrations as $migration) {
            Artisan::call('migrate', [
                '--path' => database_path('migrations/'.$migration),
                '--realpath' => true,
                '--force' => true,
            ]);
        }
    }

    public function test_template_renderer_replaces_only_allowed_variables_and_escapes_values()
    {
        $template = (object) [
            'subject' => 'Contrato de {{contractable.name}}',
            'content' => '<p>{{contractable.name}}</p><p>{{contractable.email}}</p><p>{{unknown.value}}</p>',
            'version' => 1,
            'id' => 4,
            'type' => (object) ['name' => 'Servicios'],
        ];
        $client = (object) [
            'name' => '<Empresa>',
            'lastname' => 'SAS',
            'email' => 'empresa@example.test',
            'phone' => '3000000000',
            'identification' => '900000000',
        ];

        $reflection = new \ReflectionMethod($this, 'Contract_RenderTemplate');
        $reflection->setAccessible(true);
        $rendered = $reflection->invoke($this, $template, $client, 'Servicios 2026', '', '2026-08-17', null);

        $this->assertSame('Contrato de &lt;Empresa&gt; SAS', $rendered['subject']);
        $this->assertStringContainsString('&lt;Empresa&gt; SAS', $rendered['content']);
        $this->assertStringContainsString('{{unknown.value}}', $rendered['content']);
        $this->assertSame(4, $rendered['data']['template_id']);
    }

    public function test_schedule_processing_is_idempotent_for_the_same_target_and_run()
    {
        $type = contract_type::create(['name' => 'Servicios', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla base',
            'subject' => 'Contrato {{contractable.name}}',
            'content' => '<p>Hola {{contractable.name}}</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create([
            'unique_id' => 'CLIENT-001',
            'name' => 'Cliente Uno',
            'lastname' => 'SAS',
            'email' => 'cliente@example.test',
            'active' => true,
        ]);
        $scheduledFor = Carbon::parse('2026-08-17 08:00:00');
        $schedule = contract_schedule::create([
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato mensual',
            'frequency' => 'monthly',
            'interval_value' => 1,
            'next_run_at' => $scheduledFor,
            'send_automatically' => false,
            'active' => true,
        ]);

        $first = $this->Contract_ProcessSchedules($scheduledFor->copy()->addMinute());
        $this->assertSame(1, $first['data']['created']);
        $this->assertSame(1, contract::count());
        $this->assertStringContainsString('Cliente Uno SAS', contract::first()->content);

        $schedule = $schedule->fresh();
        $schedule->next_run_at = $scheduledFor;
        $schedule->save();
        $second = $this->Contract_ProcessSchedules($scheduledFor->copy()->addMinute());

        $this->assertSame(0, $second['data']['created']);
        $this->assertSame(1, $second['data']['skipped']);
        $this->assertSame(1, contract::count());
    }

    public function test_template_renderer_resolves_client_license_and_income_variables()
    {
        $template = (object) [
            'subject' => 'Resumen de {{client.name}}',
            'content' => '<p>{{client.name}} {{client.lastname}}|{{licenses.count}}|{{licenses.total_value}}|{{incomes.count}}|{{incomes.total}}</p>',
            'version' => 1,
            'id' => 9,
            'type' => (object) ['name' => 'Servicios'],
            'variables' => [],
        ];
        $client = client::create([
            'unique_id' => 'CLIENT-002',
            'name' => 'Cliente Dos',
            'lastname' => 'SAS',
            'email' => 'dos@example.test',
            'active' => true,
        ]);
        license::forceCreate([
            'unique_id' => 'LICENSE-001',
            'client_id' => $client->id,
            'name' => 'Licencia ERP',
            'value' => 250000,
            'description' => 'Servicio',
            'active' => true,
        ]);
        income::forceCreate([
            'unique_id' => 'INCOME-001',
            'client_id' => $client->id,
            'client_name' => 'Cliente Dos SAS',
            'description' => 'Servicio mensual',
            'total' => 500000,
        ]);

        $reflection = new \ReflectionMethod($this, 'Contract_RenderTemplate');
        $reflection->setAccessible(true);
        $rendered = $reflection->invoke($this, $template, $client, 'Servicios 2026', '', '2026-08-17', null);

        $this->assertStringContainsString('Cliente Dos SAS|1|250000|1|500000', $rendered['content']);
    }

    public function test_template_creation_normalizes_custom_variable_definitions()
    {
        $type = contract_type::create(['name' => 'Servicios', 'active' => true]);
        $response = $this->Contract_CreateTemplate(
            $type->id,
            'Plantilla con variables',
            'Contrato {{custom.monthly_fee}}',
            '<p>Valor: {{custom.monthly_fee}}</p>',
            true,
            [['key' => 'Monthly Fee', 'label' => 'Valor mensual', 'type' => 'number', 'default_value' => '100', 'required' => true]]
        );

        $this->assertSame(1, $response['status']);
        $this->assertSame('custom.monthly_fee', $response['template']->variables[0]['key']);
        $this->assertSame('number', $response['template']->variables[0]['type']);
    }

    public function test_template_renderer_preserves_safe_styles_and_replaces_custom_variables()
    {
        $template = (object) [
            'subject' => 'Contrato {{custom.role}} {{contract.unique_id}}',
            'content' => '<p style="text-align: center; font-weight: bold"><script>alert(1)</script>{{contractable.name}} - {{custom.role}}</p>',
            'version' => 2,
            'id' => 8,
            'type' => (object) ['name' => 'Servicios'],
            'variables' => [
                ['key' => 'role', 'label' => 'Rol', 'type' => 'text', 'required' => true, 'default_value' => ''],
            ],
        ];
        $client = (object) [
            'name' => 'Cliente Uno',
            'lastname' => 'SAS',
            'email' => 'cliente@example.test',
        ];

        $reflection = new \ReflectionMethod($this, 'Contract_RenderTemplate');
        $reflection->setAccessible(true);
        $contract = (object) ['id' => 17, 'unique_id' => 'CONTRACT-017'];
        $rendered = $reflection->invoke($this, $template, $client, 'Servicios 2026', '', '2026-08-17', null, ['custom.role' => '<Titular>'], $contract);

        $this->assertSame('Contrato &lt;Titular&gt; CONTRACT-017', $rendered['subject']);
        $this->assertStringContainsString('text-align: center', $rendered['content']);
        $this->assertStringContainsString('Cliente Uno SAS - &lt;Titular&gt;', $rendered['content']);
        $this->assertStringNotContainsString('<script>', $rendered['content']);
        $this->assertSame('<Titular>', $rendered['data']['custom_variables']['custom.role']);
    }
}