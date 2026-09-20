<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('twilio_sid', 64)->nullable()->unique();
            $table->string('direction', 20)->index();
            $table->string('from', 80);
            $table->string('to', 80);
            $table->longText('body')->nullable();
            $table->string('message_type', 40)->default('text');
            $table->json('media')->nullable();
            $table->string('content_sid', 64)->nullable()->index();
            $table->json('content_variables')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->unsignedInteger('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('send_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('status_updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};