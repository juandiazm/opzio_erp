<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->string('twilio_sid', 64)->nullable()->index()->after('body');
            $table->string('twilio_status', 50)->nullable()->after('twilio_sid');
            $table->unsignedInteger('twilio_error_code')->nullable()->after('twilio_status');
            $table->text('twilio_error_message')->nullable()->after('twilio_error_code');
            $table->dateTime('twilio_checked_at')->nullable()->after('twilio_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->dropIndex(['twilio_sid']);
            $table->dropColumn([
                'twilio_sid',
                'twilio_status',
                'twilio_error_code',
                'twilio_error_message',
                'twilio_checked_at',
            ]);
        });
    }
};