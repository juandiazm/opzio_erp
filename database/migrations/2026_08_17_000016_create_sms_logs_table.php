<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsLogsTable extends Migration
{
    public function up()
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('recipient_name', 150)->nullable();
            $table->string('to', 30);
            $table->longText('body');
            $table->tinyInteger('attempts')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('send_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->string('notification_batch', 100)->nullable()->index();
            $table->unsignedBigInteger('resend_of_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_logs');
    }
}