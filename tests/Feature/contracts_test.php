<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\contract;
use App\Models\contract_schedule;
use App\Models\contract_template;
use App\Models\contract_type;
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

        $migrations = [
            '2026_08_17_000000_create_contract_types_table.php',
            '2026_08_17_000001_create_contract_templates_table.php',
            '2026_08_17_000002_create_contracts_table.php',
            '2026_08_17_000003_create_contract_schedules_table.php',
            '2026_08_17_000005_add_schedule_foreign_key_to_contracts_table.php',
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
}