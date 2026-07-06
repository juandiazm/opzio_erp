<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->string('subject');
            $table->string('view', 100);
            $table->string('from', 150);
            $table->string('as', 50)->nullable();
            $table->longText('to');
            $table->string('bcc', 150)->nullable();
            $table->longText('mail_data');
            $table->tinyInteger('attemps')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->dateTime('sent_at')->nullable();
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
        Schema::dropIfExists('mail_logs');
    }
}
