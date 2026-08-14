<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObservabilityTables extends Migration
{
    public function up()
    {
        Schema::create('observability_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->string('hostname', 255)->nullable();
            $table->string('environment', 50)->default('production');
            $table->unsignedInteger('config_version')->default(1);
            $table->boolean('enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('observability_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('observability_hosts')->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('name', 150);
            $table->string('path', 500);
            $table->string('environment', 50)->default('production');
            $table->boolean('enabled')->default(true);
            $table->string('php_version', 30)->nullable();
            $table->string('fpm_pool', 100)->nullable();
            $table->string('fpm_status_url', 500)->nullable();
            $table->string('nginx_access_log', 500)->nullable();
            $table->string('nginx_error_log', 500)->nullable();
            $table->string('attribution_mode', 30)->default('approximate');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['host_id', 'key']);
            $table->index(['host_id', 'enabled']);
        });

        Schema::create('observability_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('observability_hosts')->cascadeOnDelete();
            $table->string('agent_id', 100)->unique();
            $table->string('version', 100)->nullable();
            $table->string('commit_sha', 100)->nullable();
            $table->unsignedInteger('config_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('spool_bytes')->default(0);
            $table->unsignedInteger('spool_batches')->default(0);
            $table->unsignedBigInteger('uptime_seconds')->default(0);
            $table->json('collection_errors')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['host_id', 'enabled']);
            $table->index('last_seen_at');
        });

        Schema::create('observability_ingest_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 100)->unique();
            $table->string('agent_id', 100);
            $table->timestamp('captured_at');
            $table->string('payload_hash', 64);
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['agent_id', 'captured_at']);
        });

        Schema::create('observability_host_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('observability_hosts')->cascadeOnDelete();
            $table->string('agent_id', 100);
            $table->timestamp('sampled_at');
            $table->decimal('cpu_percent', 8, 3)->nullable();
            $table->decimal('load1', 10, 3)->nullable();
            $table->decimal('load5', 10, 3)->nullable();
            $table->decimal('load15', 10, 3)->nullable();
            $table->unsignedBigInteger('memory_total_bytes')->nullable();
            $table->unsignedBigInteger('memory_available_bytes')->nullable();
            $table->unsignedBigInteger('swap_total_bytes')->nullable();
            $table->unsignedBigInteger('swap_free_bytes')->nullable();
            $table->unsignedBigInteger('network_rx_bytes')->nullable();
            $table->unsignedBigInteger('network_tx_bytes')->nullable();
            $table->unsignedBigInteger('disk_total_bytes')->nullable();
            $table->unsignedBigInteger('disk_free_bytes')->nullable();
            $table->unsignedBigInteger('disk_used_bytes')->nullable();
            $table->decimal('disk_used_percent', 8, 3)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['host_id', 'sampled_at']);
        });

        Schema::create('observability_project_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('observability_projects')->cascadeOnDelete();
            $table->string('agent_id', 100);
            $table->timestamp('sampled_at');
            $table->string('attribution_mode', 30)->default('approximate');
            $table->decimal('cpu_percent', 8, 3)->nullable();
            $table->unsignedBigInteger('memory_rss_bytes')->nullable();
            $table->unsignedBigInteger('memory_pss_bytes')->nullable();
            $table->unsignedInteger('process_count')->nullable();
            $table->unsignedInteger('fpm_active_processes')->nullable();
            $table->unsignedInteger('fpm_idle_processes')->nullable();
            $table->unsignedInteger('fpm_listen_queue')->nullable();
            $table->unsignedInteger('fpm_max_listen_queue')->nullable();
            $table->unsignedInteger('fpm_max_children_reached')->nullable();
            $table->unsignedInteger('fpm_slow_requests')->nullable();
            $table->unsignedBigInteger('storage_total_bytes')->nullable();
            $table->unsignedBigInteger('storage_files')->nullable();
            $table->unsignedBigInteger('storage_directories')->nullable();
            $table->unsignedInteger('storage_scan_duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'sampled_at']);
        });

        Schema::create('observability_http_buckets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('observability_projects')->cascadeOnDelete();
            $table->string('agent_id', 100);
            $table->timestamp('bucket_start');
            $table->unsignedSmallInteger('bucket_seconds')->default(60);
            $table->unsignedBigInteger('requests_total')->default(0);
            $table->unsignedBigInteger('status_2xx')->default(0);
            $table->unsignedBigInteger('status_3xx')->default(0);
            $table->unsignedBigInteger('status_4xx')->default(0);
            $table->unsignedBigInteger('status_5xx')->default(0);
            $table->unsignedBigInteger('status_499')->default(0);
            $table->unsignedBigInteger('status_500')->default(0);
            $table->unsignedBigInteger('status_502')->default(0);
            $table->unsignedBigInteger('status_503')->default(0);
            $table->unsignedBigInteger('status_504')->default(0);
            $table->unsignedBigInteger('request_bytes')->default(0);
            $table->unsignedBigInteger('response_bytes')->default(0);
            $table->unsignedBigInteger('latency_count')->default(0);
            $table->decimal('latency_sum_ms', 14, 3)->default(0);
            $table->decimal('p50_ms', 12, 3)->nullable();
            $table->decimal('p95_ms', 12, 3)->nullable();
            $table->decimal('p99_ms', 12, 3)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'bucket_start']);
        });

        Schema::create('observability_storage_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('observability_projects')->cascadeOnDelete();
            $table->string('agent_id', 100);
            $table->timestamp('sampled_at');
            $table->unsignedBigInteger('total_bytes')->nullable();
            $table->unsignedBigInteger('files')->nullable();
            $table->unsignedBigInteger('directories')->nullable();
            $table->unsignedInteger('scan_duration_ms')->nullable();
            $table->json('breakdown')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'sampled_at']);
        });

        Schema::create('observability_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('observability_hosts')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('observability_projects')->nullOnDelete();
            $table->string('agent_id', 100);
            $table->string('event_type', 100);
            $table->string('severity', 20)->default('info');
            $table->timestamp('occurred_at');
            $table->string('message', 1000)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['host_id', 'occurred_at']);
            $table->index(['project_id', 'occurred_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('observability_events');
        Schema::dropIfExists('observability_storage_samples');
        Schema::dropIfExists('observability_http_buckets');
        Schema::dropIfExists('observability_project_samples');
        Schema::dropIfExists('observability_host_samples');
        Schema::dropIfExists('observability_ingest_batches');
        Schema::dropIfExists('observability_agents');
        Schema::dropIfExists('observability_projects');
        Schema::dropIfExists('observability_hosts');
    }
}
