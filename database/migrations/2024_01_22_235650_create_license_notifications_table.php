<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLicenseNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('license_notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('license_id')->unsigned();
            $table->foreign('license_id')->references('id')->on('licenses');
            $table->string('email', 100)->nullable();
            $table->string('phone', 100);
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('license_notifications');
    }
}
