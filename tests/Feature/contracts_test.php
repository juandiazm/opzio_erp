<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\contract;
use App\Models\contract_template;
use App\Models\contract_type;
use App\Models\employee;
use App\Models\income;
use App\Models\license;
use App\Models\license_notification;
use App\Mail\CustomMail;
use App\traits\contracts_trait;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $table->integer('recurrence_months')->nullable();
            $table->tinyInteger('billing_day')->nullable();
            $table->integer('days_to_expire')->nullable();
            $table->dateTime('last_billing_date')->nullable();
            $table->date('last_payed_date')->nullable();
            $table->date('next_billing_date')->nullable();
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
        Schema::create('license_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('license_id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(1);
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
            '2026_08_17_000007_add_sources_and_license_to_contracts_table.php',
            '2026_08_17_000008_add_sources_and_license_to_contract_schedules_table.php',
            '2026_08_17_000009_seed_service_contract_template.php',
            '2026_08_17_000010_update_service_contract_template_for_license.php',
            '2026_08_17_000011_normalize_service_contract_template_layout.php',
            '2026_08_17_000012_fix_service_contract_template_license_period.php',
            '2026_08_17_000013_rebuild_service_contract_content_after_license_period_fix.php',
            '2026_08_17_000014_move_contract_schedules_to_contract_recurrence.php',
            '2026_08_17_000015_add_contract_signature_workflow.php',
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

    public function test_contract_recurrence_creates_a_successive_clone_and_transfers_activation()
    {
        $type = contract_type::create(['name' => 'Servicios', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla base',
            'subject' => 'Contrato {{contractable.name}}',
            'content' => '<p>Hola {{contractable.name}}: {{contract.start_date}} - {{contract.end_date}}</p>',
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
        $response = $this->Contract_CreateContract([
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato mensual',
            'subject' => '',
            'start_date' => '2026-07-17',
            'end_date' => '2026-08-17',
            'recurrence_enabled' => true,
            'recurrence_frequency' => 'monthly',
            'recurrence_interval' => 1,
            'recurrence_next_at' => '2026-08-17 08:00:00',
        ]);

        $this->assertSame(1, $response['status']);
        $parent = $response['contract'];
        $first = $this->Contract_ProcessRecurrences('2026-08-17 08:01:00');
        $this->assertSame(1, $first['data']['created']);
        $this->assertFalse((bool) contract::find($parent->id)->recurrence_enabled);
        $child = contract::where('recurrence_parent_id', $parent->id)->first();
        $this->assertNotNull($child);
        $this->assertTrue((bool) $child->recurrence_enabled);
        $this->assertSame('2026-08-17', $child->start_date->format('Y-m-d'));
        $this->assertSame('2026-09-17', $child->end_date->format('Y-m-d'));
        $second = $this->Contract_ProcessRecurrences('2026-08-17 08:01:00');
        $this->assertSame(0, $second['data']['created']);
        $this->assertSame(2, contract::count());
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
            '<div style="text-align: center; font-size: 34px; font-weight: bold; color: #220245; margin: 0 0 18px 0;">opzio</div><div style="border-top: 2px solid #220245; margin: 0 0 28px 0;"></div><p>Valor: {{custom.monthly_fee}}</p><div style="border-top: 2px solid #220245; margin: 28px 0 0 0; padding-top: 10px; color: #222;"><span>legal@opzio.co</span><span style="float: right;">www.opzio.co</span></div>',
            true,
            [['key' => 'Monthly Fee', 'label' => 'Valor mensual', 'type' => 'number', 'default_value' => '100', 'required' => true]]
        );

        $this->assertSame(1, $response['status']);
        $this->assertSame('custom.monthly_fee', $response['template']->variables[0]['key']);
        $this->assertSame('number', $response['template']->variables[0]['type']);
        $this->assertStringContainsString('Valor: {{custom.monthly_fee}}', $response['template']->content);
        $this->assertStringNotContainsString('font-size: 34px', $response['template']->content);
        $this->assertStringNotContainsString('padding-top: 10px', $response['template']->content);
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

    public function test_contract_creation_requires_a_template_and_resolves_multiple_sources()
    {
        $type = contract_type::create(['name' => 'Servicios', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla multifuente',
            'subject' => 'Acuerdo {{client.name}}',
            'content' => '<p>{{client.name}} / {{employee.name}}</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create(['name' => 'Cliente Fuente', 'lastname' => 'SAS', 'active' => true]);
        $employee = employee::forceCreate(['name' => 'Empleado Fuente', 'last_name' => 'Uno', 'state' => true]);

        $directContentResponse = $this->Contract_CreateContract([
            'contract_type_id' => $type->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato directo',
            'subject' => 'Asunto directo',
            'content' => '<p>No debe guardarse</p>',
            'generate' => 0,
        ]);
        $this->assertSame(0, $directContentResponse['status']);

        $response = $this->Contract_CreateContract([
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => employee::class,
            'contractable_id' => $employee->id,
            'sources' => [
                ['type' => 'employee', 'id' => $employee->id],
                ['type' => 'client', 'id' => $client->id],
            ],
            'name' => 'Contrato multifuente',
            'subject' => '',
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame('generated', $response['contract']->status);
        $this->assertCount(2, $response['contract']->sources);
        $this->assertStringContainsString('Cliente Fuente / Empleado Fuente', $response['contract']->content);
    }

    public function test_license_selects_its_client_and_can_seed_contract_recurrence()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 12:00:00'));
        try {
            $type = contract_type::create(['name' => 'Licencias', 'active' => true]);
            $template = contract_template::create([
                'contract_type_id' => $type->id,
                'name' => 'Plantilla de licencia',
                'subject' => 'Licencia {{client.name}}',
                'content' => '<p>{{client.name}} - {{license.name}}</p>',
                'version' => 1,
                'active' => true,
            ]);
            $client = client::create(['name' => 'Cliente Licencia', 'lastname' => 'SAS', 'active' => true]);
            $license = license::forceCreate([
                'unique_id' => 'LICENSE-SYNC-001',
                'client_id' => $client->id,
                'name' => 'Licencia recurrente',
                'value' => 100,
                'type' => 1,
                'recurrence_months' => 3,
                'last_billing_date' => '2026-06-01 00:00:00',
                'next_billing_date' => '2026-09-01',
                'active' => true,
            ]);

            $response = $this->Contract_CreateContract([
                'contract_type_id' => $type->id,
                'contract_template_id' => $template->id,
                'sources' => [
                    ['type' => 'license', 'id' => $license->id],
                ],
                'name' => 'Contrato de licencia',
                'subject' => '',
                'recurrence_enabled' => true,
                'recurrence_frequency' => 'monthly',
                'recurrence_interval' => 3,
                'recurrence_next_at' => '2026-09-01 00:00:00',
            ]);

            $this->assertSame(1, $response['status']);
            $this->assertSame($license->id, $response['contract']->license_id);
            $this->assertTrue((bool) $response['contract']->recurrence_enabled);
            $this->assertSame('monthly', $response['contract']->recurrence_frequency);
            $this->assertSame(3, (int) $response['contract']->recurrence_interval);
            $this->assertSame(client::class, $response['contract']->contractable_type);
            $this->assertSame($client->id, (int) $response['contract']->contractable_id);
            $this->assertSame('2026-06-01', $response['contract']->start_date->format('Y-m-d'));
            $this->assertSame('2026-09-01', $response['contract']->end_date->format('Y-m-d'));
            $this->assertEqualsCanonicalizing(['license', 'client'], collect($response['contract']->sources)->pluck('type')->all());
            $this->assertStringContainsString('Cliente Licencia - Licencia recurrente', $response['contract']->content);
            $this->assertSame('2026-09-01', $response['contract']->recurrence_next_at->format('Y-m-d'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_seeded_service_template_contains_pdf_contract_and_generates_without_unresolved_variables()
    {
        $type = contract_type::where('name', 'Prestación de servicios')->first();
        $template = contract_template::where('name', 'Contrato de prestación de servicios - infraestructura y soporte')->first();

        $this->assertNotNull($type);
        $this->assertNotNull($template);
        $this->assertTrue((bool) $template->active);
        $this->assertCount(22, $template->variables);
        $this->assertContains('custom.contract_number', array_column($template->variables, 'key'));
        $this->assertContains('custom.contractor_domain', array_column($template->variables, 'key'));
        $this->assertNotContains('custom.contract_duration', array_column($template->variables, 'key'));
        $this->assertNotContains('custom.start_date_text', array_column($template->variables, 'key'));
        $this->assertNotContains('custom.end_date_text', array_column($template->variables, 'key'));
        $this->assertStringContainsString('{{contract.start_date}}', $template->content);
        $this->assertStringContainsString('{{contract.end_date}}', $template->content);
        $this->assertStringContainsString('{{license.value_string}}', $template->content);
        $this->assertStringContainsString('{{license.recurrence_string}}', $template->content);
        $this->assertStringNotContainsString('font-size: 34px', $template->content);
        $this->assertStringNotContainsString('padding-top: 10px', $template->content);
        $this->assertStringContainsString('CLÁUSULA VIGÉSIMA SÉPTIMA – FIRMAS', $template->content);

        $client = client::create([
            'unique_id' => 'CLIENT-PDF-001',
            'name' => 'OHB LTDA.',
            'email' => 'ohb.ltda@example.test',
            'phone' => '6012953967',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'unique_id' => 'LICENSE-PDF-001',
            'client_id' => $client->id,
            'name' => 'Alojamiento y soporte',
            'value' => 6000000,
            'type' => 1,
            'recurrence_months' => 6,
            'billing_day' => 20,
            'days_to_expire' => 4,
            'last_billing_date' => '2026-08-01',
            'next_billing_date' => '2027-02-05',
            'active' => true,
        ]);

        $response = $this->Contract_CreateContract([
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'license_id' => $license->id,
            'name' => 'Contrato de prestación de servicios 003-2026',
            'subject' => '',
            'start_date' => '2026-08-01',
            'end_date' => '2027-02-05',
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame('generated', $response['contract']->status);
        $this->assertStringContainsString('Contrato de prestación de servicios No. 003-2026', $response['contract']->subject);
        $this->assertStringContainsString('OPZIO S.A.S.', $response['contract']->content);
        $this->assertStringContainsString('2026-08-01', $response['contract']->content);
        $this->assertStringContainsString('2027-02-05', $response['contract']->content);
        $this->assertStringContainsString('$ 6.000.000 M/CTE', $response['contract']->content);
        $this->assertStringContainsString('Recurrente - 6 meses', $response['contract']->content);
        $this->assertStringNotContainsString('4 días', $response['contract']->content);
        $this->assertStringContainsString('CLÁUSULA DÉCIMA SEGUNDA', $response['contract']->content);
        $this->assertStringContainsString('border: 1px solid #222', $response['contract']->content);
        $this->assertStringNotContainsString('{{', $response['contract']->content);
    }

    public function test_seeded_service_template_uses_effective_license_period_and_exact_recurrence()
    {
        $type = contract_type::where('name', 'Prestación de servicios')->first();
        $template = contract_template::where('name', 'Contrato de prestación de servicios - infraestructura y soporte')->first();
        $client = client::create([
            'unique_id' => 'CLIENT-PERIOD-001',
            'name' => 'Cliente Periodo',
            'email' => 'periodo@example.test',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'unique_id' => 'LICENSE-PERIOD-001',
            'client_id' => $client->id,
            'name' => 'Licencia anual',
            'value' => 1200000,
            'type' => 1,
            'recurrence_months' => 12,
            'billing_day' => 20,
            'days_to_expire' => 5,
            'last_payed_date' => '2026-08-17',
            'active' => true,
        ]);

        $response = $this->Contract_CreateContract([
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'license_id' => $license->id,
            'name' => 'Contrato anual con periodo de licencia',
            'subject' => '',
            'recurrence_enabled' => true,
            'recurrence_frequency' => 'monthly',
            'recurrence_interval' => 6,
            'recurrence_next_at' => '2027-02-05 00:00:00',
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame('2026-08-17', $response['contract']->start_date->format('Y-m-d'));
        $this->assertSame('2027-08-17', $response['contract']->end_date->format('Y-m-d'));
        $this->assertStringContainsString('2026-08-17', $response['contract']->content);
        $this->assertStringContainsString('2027-08-17', $response['contract']->content);
        $this->assertStringContainsString('Recurrente - 12 meses', $response['contract']->content);
        $this->assertStringNotContainsString('12 meses y 5 días', $response['contract']->content);
    }

    public function test_layout_migration_removes_legacy_chrome_from_existing_contracts_idempotently()
    {
        $template = contract_template::where('name', 'Contrato de prestación de servicios - infraestructura y soporte')->first();
        $client = client::create([
            'unique_id' => 'CLIENT-LEGACY-001',
            'name' => 'Cliente Legacy',
            'email' => 'legacy@example.test',
            'active' => true,
        ]);
        $legacyContent = '<div style="font-family: Arial; color: #111;"><div style="text-align: center; font-size: 34px; font-weight: bold; color: #220245; margin: 0 0 18px 0;">opzio</div><div style="border-top: 2px solid #220245; margin: 0 0 28px 0;"></div><p>Contenido vigente</p><div style="border-top: 2px solid #220245; margin: 28px 0 0 0; padding-top: 10px; color: #222;"><span>legal@opzio.co</span><span style="float: right;">www.opzio.co</span></div></div>';
        $contract = contract::create([
            'unique_id' => 'CONTRACT-LEGACY-001',
            'contract_type_id' => $template->contract_type_id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato legacy',
            'subject' => 'Contrato legacy',
            'content' => $legacyContent,
            'status' => 'generated',
        ]);

        (new \NormalizeServiceContractTemplateLayout())->up();
        $cleanContent = contract::find($contract->id)->content;
        $this->assertStringContainsString('Contenido vigente', $cleanContent);
        $this->assertStringNotContainsString('font-size: 34px', $cleanContent);
        $this->assertStringNotContainsString('padding-top: 10px', $cleanContent);

        (new \NormalizeServiceContractTemplateLayout())->up();
        $this->assertSame($cleanContent, contract::find($contract->id)->content);
    }

    public function test_content_backfill_rebuilds_outdated_contracts_with_license_period_data()
    {
        $template = contract_template::where('name', 'Contrato de prestación de servicios - infraestructura y soporte')->first();
        $client = client::create([
            'unique_id' => 'CLIENT-BACKFILL-001',
            'name' => 'Cliente Backfill',
            'email' => 'backfill@example.test',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'unique_id' => 'LICENSE-BACKFILL-001',
            'client_id' => $client->id,
            'name' => 'Licencia mensual',
            'value' => 100000,
            'type' => 1,
            'recurrence_months' => 1,
            'days_to_expire' => 5,
            'last_payed_date' => '2026-08-17',
            'active' => true,
        ]);
        $contract = contract::create([
            'unique_id' => 'CONTRACT-BACKFILL-001',
            'contract_type_id' => $template->contract_type_id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'license_id' => $license->id,
            'sources' => [
                ['type' => 'license', 'id' => $license->id],
                ['type' => 'client', 'id' => $client->id],
            ],
            'name' => 'Contrato backfill',
            'subject' => $template->subject,
            'content' => '<p>1 meses y 5 días</p>',
            'status' => 'generated',
            'start_date' => '2026-08-17',
            'end_date' => '2027-08-17',
            'generation_data' => [
                'template_version' => 1,
                'custom_variables' => [],
            ],
        ]);

        (new \RebuildServiceContractContentAfterLicensePeriodFix())->up();
        $content = contract::find($contract->id)->content;

        $this->assertStringContainsString('2026-08-17', $content);
        $this->assertStringContainsString('2027-08-17', $content);
        $this->assertStringContainsString('Recurrente - 1 mes', $content);
        $this->assertStringNotContainsString('1 meses y 5 días', $content);
    }

    public function test_send_options_prioritize_active_license_notifications_then_client_email()
    {
        $type = contract_type::create(['name' => 'Envio', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla de envio',
            'subject' => 'Contrato de envio',
            'content' => '<p>Contenido</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create([
            'name' => 'Cliente Destinatario',
            'email' => 'cliente@example.test',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia de envio',
            'value' => 100,
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'notificacion@example.test',
            'active' => true,
        ]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'inactiva@example.test',
            'active' => false,
        ]);
        $contract = contract::create([
            'unique_id' => 'CONTRACT-SEND-001',
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'license_id' => $license->id,
            'name' => 'Contrato con destinatarios',
            'subject' => 'Contrato de envio',
            'content' => '<p>Contenido</p>',
            'status' => 'generated',
        ]);

        $options = $this->Contract_GetSendOptions($contract->id);

        $this->assertSame(1, $options['status']);
        $this->assertSame(['notificacion@example.test'], $options['default_recipients']);
        $this->assertSame('license_notification', $options['recipient_options'][0]['source']);

        license_notification::where('email', 'notificacion@example.test')->delete();
        $fallback = $this->Contract_GetSendOptions($contract->id);
        $this->assertSame(['cliente@example.test'], $fallback['default_recipients']);

        $client->email = null;
        $client->save();
        $empty = $this->Contract_GetSendOptions($contract->id);
        $this->assertSame([], $empty['default_recipients']);
    }

    public function test_send_contract_uses_requested_recipients_and_legal_sender_and_reply_to()
    {
        $type = contract_type::create(['name' => 'Envio personalizado', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla de envio personalizado',
            'subject' => 'Contrato personalizado',
            'content' => '<p>Contenido</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create(['name' => 'Cliente', 'email' => 'cliente@example.test', 'active' => true]);
        $contract = contract::create([
            'unique_id' => 'CONTRACT-SEND-002',
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato personalizado',
            'subject' => 'Contrato personalizado',
            'content' => '<p>Contenido</p>',
            'status' => 'generated',
        ]);
        $existingPdf = '%PDF-1.4 existing document';
        Storage::disk('local')->put('contracts/pdfs/CONTRACT-SEND-002.pdf', $existingPdf);

        Mail::fake();
        $response = $this->Contract_SendContract($contract->id, [
            'primero@example.test',
            'segundo@example.test',
        ]);

        $this->assertSame(1, $response['status'], $response['message']);
        $this->assertSame('pending_signature', $response['contract']->status);
        $this->assertSame('sent', $response['contract']->send_status);
        $this->assertSame($existingPdf, Storage::disk('local')->get('contracts/pdfs/CONTRACT-SEND-002.pdf'));
        $secondResponse = $this->Contract_SendContract($contract->id, [
            'primero@example.test',
            'segundo@example.test',
        ]);
        $this->assertSame(1, $secondResponse['status']);
        $this->assertArrayNotHasKey('already_sent', $secondResponse);
        Mail::assertQueued(CustomMail::class, 2);
        Mail::assertQueued(CustomMail::class, function ($mail) {
            return ($mail->fromDetails['address'] ?? null) === 'legal@opzio.co'
                && ($mail->replyToDetails['address'] ?? null) === 'info@opzio.co'
                && $mail->View === 'mail.contract'
                && ($mail->ViewData['contract']['holder'] ?? null) === 'Cliente'
                && ($mail->ViewData['contract']['name'] ?? null) === 'Contrato personalizado'
                && ($mail->ViewData['contract']['subject'] ?? null) === 'Contrato personalizado'
                && ($mail->ViewData['contract']['type'] ?? null) === 'Envio personalizado'
                && ($mail->ViewData['contract']['unique_id'] ?? null) === 'CONTRACT-SEND-002'
                && str_contains((string) ($mail->ViewData['contract']['signature_url'] ?? ''), '/public/contracts/CONTRACT-SEND-002/signature/')
                && !array_key_exists('content', $mail->ViewData['contract'])
                && is_array($mail->files)
                && ($mail->files[0]['name'] ?? null) === 'Contrato-CONTRACT-SEND-002.pdf'
                && is_file($mail->files[0]['path'] ?? '');
        });
    }

    public function test_send_contract_requires_an_existing_pdf_and_marks_failed_send()
    {
        $type = contract_type::create(['name' => 'Envio sin PDF', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla sin PDF',
            'subject' => 'Contrato sin PDF',
            'content' => '<p>Contenido</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create(['name' => 'Cliente sin PDF', 'email' => 'sinpdf@example.test', 'active' => true]);
        $contract = contract::create([
            'unique_id' => 'CONTRACT-NO-PDF-001',
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato sin PDF',
            'subject' => 'Contrato sin PDF',
            'content' => '<p>Contenido</p>',
            'status' => 'generated',
        ]);

        $response = $this->Contract_SendContract($contract->id, ['sinpdf@example.test']);

        $this->assertSame(0, $response['status']);
        $this->assertStringContainsString('PDF no existe', $response['message']);
        $this->assertSame('failed', contract::find($contract->id)->send_status);
    }

    public function test_daily_recurrence_job_marks_unsigned_expired_contracts()
    {
        $type = contract_type::create(['name' => 'Estado vencido', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla vencida',
            'subject' => 'Contrato vencido',
            'content' => '<p>Contenido</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create(['name' => 'Cliente vencido', 'email' => 'vencido@example.test', 'active' => true]);
        $contract = contract::create([
            'unique_id' => 'CONTRACT-EXPIRED-001',
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato vencido',
            'subject' => 'Contrato vencido',
            'content' => '<p>Contenido</p>',
            'status' => 'generated',
            'end_date' => '2026-08-16',
        ]);

        $this->Contract_ProcessRecurrences('2026-08-17 00:05:00');

        $this->assertSame('expired', contract::find($contract->id)->status);
    }

    public function test_public_signature_url_accepts_pdf_and_blocks_after_acceptance()
    {
        $type = contract_type::create(['name' => 'Firma pública', 'active' => true]);
        $template = contract_template::create([
            'contract_type_id' => $type->id,
            'name' => 'Plantilla firma pública',
            'subject' => 'Contrato para firma',
            'content' => '<p>Contenido de firma</p>',
            'version' => 1,
            'active' => true,
        ]);
        $client = client::create(['name' => 'Cliente firma', 'email' => 'firma@example.test', 'active' => true]);
        $contract = contract::create([
            'unique_id' => 'CONTRACT-SIGNATURE-001',
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => client::class,
            'contractable_id' => $client->id,
            'name' => 'Contrato para firma',
            'subject' => 'Contrato para firma',
            'content' => '<p>Contenido de firma</p>',
            'status' => 'generated',
        ]);
        Storage::disk('local')->put('contracts/pdfs/CONTRACT-SIGNATURE-001.pdf', '%PDF-1.4 original');

        Mail::fake();
        $sendResponse = $this->Contract_SendContract($contract->id, ['firma@example.test']);
        $this->assertSame(1, $sendResponse['status']);
        $contract = contract::find($contract->id);
        $url = route('public.contract.signature', ['uniqueId' => $contract->unique_id, 'token' => $contract->signature_token]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Carga el documento firmado');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'opzio-signed-');
        file_put_contents($temporaryPath, "%PDF-1.4\nfirmado");
        try {
            $upload = new UploadedFile($temporaryPath, 'contrato-firmado.pdf', 'application/pdf', null, true);
            $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
                ->post($url, ['signed_pdf' => $upload])
                ->assertRedirect();
        } finally {
            @unlink($temporaryPath);
        }

        $contract = contract::find($contract->id);
        $this->assertSame('uploaded', $contract->signature_status);
        $this->get($url)->assertOk()->assertSee('Información diligenciada');

        $accepted = $this->Contract_ChangeSignatureStatus($contract->id, 'accepted');
        $this->assertSame(1, $accepted['status']);
        $this->assertSame('accepted', $accepted['contract']->signature_status);
        $this->assertSame('signed', $accepted['contract']->status);
        $this->get($url)->assertOk()->assertSee('enlace se encuentra cerrado');
    }

    public function test_custom_mail_does_not_duplicate_support_recipient_as_bcc()
    {
        $mail = new CustomMail(
            ['subject' => 'Contrato'],
            'mail.contract',
            ['recipient_name' => 'Cliente']
        );
        $mail->to('info@opzio.co');

        $builtMail = $mail->build();

        $this->assertCount(0, $builtMail->bcc);
    }
}