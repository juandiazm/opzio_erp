<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenIaRunRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('open_ia_run_registers', function (Blueprint $table) {
            $table->id();
            $table->string('run_id', 200)->nullable();
            $table->string('object', 200)->nullable();
            $table->string('createdAt', 200)->nullable();
            $table->string('assistant_id', 200)->nullable();
            $table->string('thread_id', 200)->nullable();
            $table->string('status_string', 200)->nullable();
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('open_ia_run_registers');
    }
}
