<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table): void {
            $table->string('ai_thread_id', 200)->nullable()->unique();
            $table->json('ai_scope')->nullable();
            $table->string('ai_scope_hash', 64)->nullable()->index();
            $table->string('ai_last_response_id', 200)->nullable();
            $table->string('ai_status', 30)->default('pending')->index();
            $table->dateTime('ai_last_processed_at')->nullable();
            $table->text('ai_handoff_reason')->nullable();
        });

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->string('ai_topic', 40)->nullable()->index();
            $table->string('ai_decision', 30)->nullable()->index();
            $table->json('ai_query')->nullable();
            $table->string('ai_response_id', 200)->nullable();
            $table->boolean('ai_generated')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropIndex(['ai_generated']);
            $table->dropIndex(['ai_decision']);
            $table->dropIndex(['ai_topic']);
            $table->dropColumn(['ai_topic', 'ai_decision', 'ai_query', 'ai_response_id', 'ai_generated']);
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table): void {
            $table->dropIndex(['ai_status']);
            $table->dropIndex(['ai_scope_hash']);
            $table->dropUnique(['ai_thread_id']);
            $table->dropColumn([
                'ai_thread_id',
                'ai_scope',
                'ai_scope_hash',
                'ai_last_response_id',
                'ai_status',
                'ai_last_processed_at',
                'ai_handoff_reason',
            ]);
        });
    }
};
