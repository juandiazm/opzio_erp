<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->string("name", 100);
            $table->string("lastname", 100)->nullable();
            $table->string("email", 100)->unique();
            $table->tinyInteger("identification_type");
            $table->string("identification", 50)->unique();
            $table->string('country', 100)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('sector', 100)->nullable();
            $table->longText('description')->nullable();
            $table->string("photo", 100)->nullable();
            $table->boolean("active")->default(1);
            $table->boolean('verified')->default(0);
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
        Schema::dropIfExists('providers');
    }
}
