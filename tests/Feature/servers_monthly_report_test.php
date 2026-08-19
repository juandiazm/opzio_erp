<?php

namespace Tests\Feature;

use App\Domain\Servers\Models\servers_host;
use App\Domain\Servers\Models\servers_project;
use App\Services\Servers\server_monthly_report_service;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class servers_monthly_report_test extends TestCase
{
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.timezone' => 'America/Bogota',
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
            $table->boolean('active')->default(true);
            $table->timestamps();
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
            '--path' => database_path('migrations/2026_08_19_000009_add_notification_name_to_servers_projects_table.php'),
            '--realpath' => true,
        ]);
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->string('subject');
            $table->string('view', 100);
            $table->string('from', 150);
            $table->string('as', 50)->nullable();
            $table->longText('to');
            $table->string('bcc', 150)->nullable();
            $table->longText('mail_data');
            $table->tinyInteger('attemps')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->dateTime('send_at')->nullable();
            $table->string('notification_batch', 100)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
        Schema::create('mail_log_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mail_log_id');
            $table->string('name', 150);
            $table->string('path', 200);
            $table->timestamps();
        });

        $host = servers_host::create([
            'key' => 'report-host',
            'name' => 'Report host',
            'hostname' => 'report-host.local',
            'environment' => 'production',
            'enabled' => true,
        ]);
        $this->project = servers_project::create([
            'host_id' => $host->id,
            'key' => 'report-project',
            'name' => 'Report project',
            'path' => '/var/www/report-project',
            'environment' => 'production',
            'enabled' => true,
            'notifications_enabled' => true,
            'notification_recipients_initialized' => true,
            'notification_name' => 'Portal comercial',
        ]);
        DB::table('servers_project_notifications')->insert([
            'project_id' => $this->project->id,
            'source_type' => 'project',
            'source_key' => 'project:report-email',
            'channel' => 'email',
            'value' => 'stakeholder@example.test',
            'recipient_name' => 'Stakeholder',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_monthly_report_translates_server_metrics_into_stakeholder_summary()
    {
        $from = now()->startOfMonth()->subMonth()->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $bucketStart = $from->copy()->addDays(10)->toDateTimeString();
        DB::table('servers_http_buckets')->insert([
            'project_id' => $this->project->id,
            'agent_id' => 'report-agent',
            'bucket_start' => $bucketStart,
            'bucket_seconds' => 60,
            'requests_total' => 1000,
            'status_2xx' => 990,
            'status_3xx' => 5,
            'status_4xx' => 4,
            'status_5xx' => 1,
            'request_bytes' => 10000,
            'response_bytes' => 50000,
            'latency_count' => 1000,
            'latency_sum_ms' => 120000,
            'p95_ms' => 240,
        ]);
        DB::table('servers_project_samples')->insert([
            'project_id' => $this->project->id,
            'agent_id' => 'report-agent',
            'sampled_at' => $bucketStart,
            'cpu_percent' => 32,
            'memory_rss_bytes' => 1000000,
            'memory_pss_bytes' => 800000,
            'fpm_listen_queue' => 0,
            'fpm_max_listen_queue' => 10,
        ]);
        DB::table('servers_storage_samples')->insert([
            'project_id' => $this->project->id,
            'agent_id' => 'report-agent',
            'sampled_at' => $bucketStart,
            'total_bytes' => 2000000,
            'files' => 40,
            'directories' => 8,
        ]);
        DB::table('servers_events')->insert([
            'host_id' => $this->project->host_id,
            'project_id' => $this->project->id,
            'agent_id' => 'report-agent',
            'event_type' => 'test',
            'severity' => 'warning',
            'occurred_at' => $bucketStart,
            'message' => 'Test event',
        ]);

        $report = app(server_monthly_report_service::class)->build($this->project, $from, $to);

        $this->assertSame(1000, $report['metrics']['traffic']['requests_total']);
        $this->assertSame('Portal comercial', $report['project']['display_name']);
        $this->assertSame(99.9, $report['metrics']['reliability']['availability_percent']);
        $this->assertSame(120.0, $report['metrics']['performance']['latency_average_ms']);
        $this->assertSame(240.0, $report['metrics']['performance']['p95_average_ms']);
        $this->assertSame(1, $report['metrics']['operations']['events_total']);
        $this->assertSame('99,90%', $report['stakeholder_summary'][0]['value']);
        $this->assertContains('El proyecto mantuvo un comportamiento estable según las métricas disponibles.', $report['recommendations']);
        $clientFacingText = json_encode([
            $report['stakeholder_summary'],
            $report['recommendations'],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('conviene', strtolower($clientFacingText));
        $this->assertStringNotContainsString('oportunidades de optimización', strtolower($clientFacingText));
        $this->assertStringNotContainsString('merecen una revisión', strtolower($clientFacingText));

        $mailHtml = view('mail.servers.monthly_report', ['Data' => ['report' => $report]])->render();
        $pdfHtml = view('pdf.servers.monthly_report', ['Data' => ['report' => $report]])->render();
        $this->assertStringContainsString('Reporte mensual de estado', $mailHtml);
        $this->assertStringContainsString('Resumen ejecutivo', $pdfHtml);
        $this->assertStringNotContainsString('Report host', $pdfHtml);
        $this->assertStringNotContainsString('/var/www/report-project', $pdfHtml);
    }

    public function test_monthly_report_dry_run_is_idempotent_safe_and_targets_active_projects()
    {
        $exitCode = Artisan::call('servers:send-monthly-report', [
            '--period' => now()->subMonth()->format('Y-m'),
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, DB::table('mail_logs')->count());
    }

    public function test_monthly_report_accepts_an_explicit_date_or_month()
    {
        $monthExitCode = Artisan::call('servers:send-monthly-report', [
            '--date' => '2026-08',
            '--project' => $this->project->id,
            '--dry-run' => true,
        ]);
        $this->assertSame(0, $monthExitCode);
        $this->assertStringContainsString('2026-08', Artisan::output());

        $dateExitCode = Artisan::call('servers:send-monthly-report', [
            '--date' => '2026-08-19',
            '--project' => $this->project->id,
            '--dry-run' => true,
        ]);
        $this->assertSame(0, $dateExitCode);
        $this->assertStringContainsString('2026-08', Artisan::output());
    }

    public function test_monthly_report_without_date_uses_the_previous_month()
    {
        $exitCode = Artisan::call('servers:send-monthly-report', [
            '--project' => $this->project->id,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(now()->subMonth()->format('Y-m'), Artisan::output());
    }

    public function test_monthly_report_rejects_period_and_date_together()
    {
        $exitCode = Artisan::call('servers:send-monthly-report', [
            '--period' => '2026-08',
            '--date' => '2026-08-19',
            '--dry-run' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('no ambos', Artisan::output());
    }

    public function test_force_dry_run_does_not_replace_pending_reports()
    {
        $batch = 'servers-monthly-report:2026-08:'.$this->project->id;
        DB::table('mail_logs')->insert([
            'unique_id' => 'PENDING-FORCE-TEST',
            'subject' => 'Reporte pendiente',
            'view' => 'mail.servers.monthly_report',
            'from' => 'soporte@opzio.co',
            'as' => 'Soporte Opzio',
            'to' => json_encode([['address' => 'stakeholder@example.test']]),
            'mail_data' => json_encode([]),
            'status' => 0,
            'notification_batch' => $batch,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('servers:send-monthly-report', [
            '--date' => '2026-08',
            '--project' => $this->project->id,
            '--force' => true,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, (int) DB::table('mail_logs')->where('notification_batch', $batch)->value('status'));
    }

    public function test_low_availability_dry_run_routes_the_full_report_to_support()
    {
        $from = now()->startOfMonth()->subMonth()->startOfMonth();
        DB::table('servers_http_buckets')->insert([
            'project_id' => $this->project->id,
            'agent_id' => 'report-agent',
            'bucket_start' => $from->copy()->addDay(),
            'bucket_seconds' => 60,
            'requests_total' => 100,
            'status_2xx' => 80,
            'status_5xx' => 20,
        ]);

        $exitCode = Artisan::call('servers:send-monthly-report', [
            '--period' => $from->format('Y-m'),
            '--project' => $this->project->id,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('soporte@opzio.co (alerta)', Artisan::output());
        $this->assertStringNotContainsString('1 destinatario(s)', Artisan::output());
    }
}
