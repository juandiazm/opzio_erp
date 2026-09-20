<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('singleton_key')->nullable();
            $table->string('name', 150);
            $table->string('provider', 40)->default('secop_soda');
            $table->string('status', 20)->default('draft');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->dateTime('last_tested_at')->nullable();
            $table->dateTime('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('singleton_key', 'tenders_connections_singleton_unique');
            $table->index(['provider', 'status'], 'tenders_connections_provider_status_idx');
        });

        Schema::create('tenders_sync_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenders_connection_id')->constrained('tenders_connections')->cascadeOnDelete();
            $table->string('source', 30);
            $table->dateTime('cursor_at')->nullable();
            $table->string('cursor_id', 200)->nullable();
            $table->dateTime('last_success_at')->nullable();
            $table->timestamps();
            $table->unique(['tenders_connection_id', 'source'], 'tenders_sync_states_connection_source_unique');
        });

        Schema::create('tenders_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenders_connection_id')->constrained('tenders_connections')->cascadeOnDelete();
            $table->string('source', 30)->default('all');
            $table->string('mode', 30)->default('manual');
            $table->string('status', 20)->default('running');
            $table->dateTime('cursor_from')->nullable();
            $table->dateTime('cursor_to')->nullable();
            $table->json('parameters')->nullable();
            $table->unsignedInteger('pages')->default(0);
            $table->unsignedInteger('rows_seen')->default(0);
            $table->unsignedInteger('rows_created')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_unchanged')->default(0);
            $table->unsignedInteger('rows_rejected')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->index(['tenders_connection_id', 'source', 'started_at'], 'tenders_sync_runs_connection_source_started_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders_sync_runs');
        Schema::dropIfExists('tenders_sync_states');
        Schema::dropIfExists('tenders_connections');
    }
};
