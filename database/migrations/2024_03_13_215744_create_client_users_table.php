<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_users', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->string('name', 100);
            $table->string('lastname', 100)->nullable();
            $table->string('username', 100)->unique();
            $table->string('email', 100)->unique();
            $table->string('phone', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('color', 10)->nullable();
            $table->string("password", 100);
            $table->string("reset_password", 100)->nullable();
            $table->dateTime("reset_password_date")->nullable();
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
        Schema::dropIfExists('client_users');
    }
}
