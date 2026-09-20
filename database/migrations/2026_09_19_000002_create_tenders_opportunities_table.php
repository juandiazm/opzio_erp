<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 30);
            $table->string('source_id', 200);
            $table->string('source_process_id', 200)->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('title', 1000);
            $table->longText('description')->nullable();
            $table->string('entity', 500);
            $table->string('entity_nit', 100)->nullable();
            $table->string('department', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency', 30)->nullable();
            $table->string('phase', 255)->nullable();
            $table->string('procurement_method', 255)->nullable();
            $table->string('contract_type', 255)->nullable();
            $table->string('category_code', 100)->nullable();
            $table->string('category_text', 1000)->nullable();
            $table->string('status', 30)->default('closed');
            $table->string('source_status', 255)->nullable();
            $table->string('opening_status', 255)->nullable();
            $table->string('source_url', 2000)->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('last_published_at')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->string('payload_hash', 64);
            $table->json('raw_payload')->nullable();
            $table->json('embedding')->nullable();
            $table->string('embedding_model', 100)->nullable();
            $table->string('embedding_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['source', 'source_id'], 'tenders_opportunities_source_id_unique');
            $table->index(['status', 'deadline_at'], 'tenders_opportunities_status_deadline_idx');
            $table->index(['source', 'last_published_at'], 'tenders_opportunities_source_published_idx');
            $table->index('source_process_id', 'tenders_opportunities_process_idx');
        });

        Schema::create('tenders_opportunity_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenders_opportunity_id')->constrained('tenders_opportunities')->cascadeOnDelete();
            $table->string('payload_hash', 64);
            $table->json('changed_fields')->nullable();
            $table->json('raw_payload')->nullable();
            $table->dateTime('observed_at');
            $table->timestamps();
            $table->index(['tenders_opportunity_id', 'observed_at'], 'tenders_opportunity_versions_observed_idx');
        });

        Schema::create('tenders_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenders_opportunity_id')->constrained('tenders_opportunities')->cascadeOnDelete();
            $table->string('source', 30);
            $table->string('source_document_id', 200);
            $table->string('process_id', 200)->nullable();
            $table->string('filename', 1000);
            $table->string('extension', 20)->nullable();
            $table->longText('description')->nullable();
            $table->string('source_url', 2000);
            $table->dateTime('source_uploaded_at')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('mime_type', 255)->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('status', 30)->default('discovered');
            $table->string('storage_path', 2000)->nullable();
            $table->longText('extracted_text')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['source', 'source_document_id'], 'tenders_documents_source_id_unique');
            $table->index(['tenders_opportunity_id', 'status'], 'tenders_documents_opportunity_status_idx');
        });

        Schema::create('tenders_document_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenders_document_id')->constrained('tenders_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->string('page_ref', 100)->nullable();
            $table->longText('text');
            $table->string('text_hash', 64);
            $table->timestamps();
            $table->unique(['tenders_document_id', 'chunk_index'], 'tenders_document_chunks_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders_document_chunks');
        Schema::dropIfExists('tenders_documents');
        Schema::dropIfExists('tenders_opportunity_versions');
        Schema::dropIfExists('tenders_opportunities');
    }
};
