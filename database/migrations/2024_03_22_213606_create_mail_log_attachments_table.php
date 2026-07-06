<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailLogAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_log_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mail_log_id');
            $table->foreign('mail_log_id')->references('id')->on('mail_logs');
            $table->string('name', 150);
            $table->string('path', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_log_attachments');
    }
}
