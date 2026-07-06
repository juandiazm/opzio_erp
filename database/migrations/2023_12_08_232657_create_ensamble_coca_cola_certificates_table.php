<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnsambleCocaColaCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ensamble_coca_cola_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 100);
            $table->string('identification');
            $table->string('name')->nullable();
            $table->string('lastname')->nullable();
            $table->boolean('downloaded')->default(0);
            $table->dateTime('downloaded_at')->nullable();
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
        Schema::dropIfExists('ensamble_coca_cola_certificates');
    }
}
