<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders_feedback_events', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 100);
            $table->foreignId('tenders_opportunity_id')->constrained('tenders_opportunities')->cascadeOnDelete();
            $table->string('actor_id', 100)->nullable();
            $table->string('event_type', 50);
            $table->string('reason_code', 100)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('weight', 8, 3)->default(0);
            $table->string('idempotency_key', 200);
            $table->timestamps();
            $table->unique(['tenant_id', 'idempotency_key'], 'tenders_feedback_tenant_idempotency_unique');
            $table->index(['tenant_id', 'tenders_opportunity_id', 'created_at'], 'tenders_feedback_opportunity_created_idx');
        });

        Schema::create('tenders_pipeline_items', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 100);
            $table->foreignId('tenders_opportunity_id')->constrained('tenders_opportunities')->cascadeOnDelete();
            $table->string('stage', 50)->default('saved');
            $table->string('owner_id', 100)->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('outcome', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'tenders_opportunity_id'], 'tenders_pipeline_items_tenant_opportunity_unique');
            $table->index(['tenant_id', 'stage'], 'tenders_pipeline_items_tenant_stage_idx');
        });

        Schema::create('tenders_pipeline_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 100);
            $table->foreignId('tenders_opportunity_id')->constrained('tenders_opportunities')->cascadeOnDelete();
            $table->string('actor_id', 100)->nullable();
            $table->string('stage', 50)->default('saved');
            $table->dateTime('due_at')->nullable();
            $table->string('outcome', 100)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'tenders_opportunity_id', 'created_at'], 'tenders_pipeline_entries_history_idx');
            $table->index(['tenant_id', 'deleted_at'], 'tenders_pipeline_entries_active_idx');
        });

        Schema::create('tenders_outbox_events', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 100);
            $table->string('event_type', 100);
            $table->string('aggregate_type', 100);
            $table->string('aggregate_id', 200);
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at');
            $table->timestamps();
            $table->index(['status', 'available_at'], 'tenders_outbox_status_available_idx');
            $table->index(['tenant_id', 'created_at'], 'tenders_outbox_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders_outbox_events');
        Schema::dropIfExists('tenders_pipeline_entries');
        Schema::dropIfExists('tenders_pipeline_items');
        Schema::dropIfExists('tenders_feedback_events');
    }
};
