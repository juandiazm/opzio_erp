<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('phone', 30);
            $table->string('business_address', 80);
            $table->string('wa_id', 40)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('profile_name', 150)->nullable();
            $table->unsignedInteger('unread_count')->default(0)->index();
            $table->text('last_message_preview')->nullable();
            $table->dateTime('last_message_at')->nullable()->index();
            $table->dateTime('last_inbound_at')->nullable();
            $table->dateTime('window_expires_at')->nullable();
            $table->dateTime('last_read_at')->nullable();
            $table->string('last_inbound_sid', 64)->nullable();
            $table->string('last_outbound_sid', 64)->nullable();
            $table->timestamps();

            $table->unique(['phone', 'business_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};