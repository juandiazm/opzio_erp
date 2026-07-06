<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string("uid", 50)->unique();
            $table->string("name", 100);
            $table->string("last_name", 100);
            $table->tinyInteger("id_type");
            $table->string("identification", 20);
            $table->string("country", 100);
            $table->string("phone", 20);
            $table->string("personal_email", 100);
            $table->string("work_email", 100);
            $table->boolean("state");
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
        Schema::dropIfExists('employees');
    }
}
