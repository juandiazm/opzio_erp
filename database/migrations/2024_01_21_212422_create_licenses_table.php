<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->boolean('active')->default(true);
            $table->bigInteger('client_id')->unsigned();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->string('name', 100);
            $table->bigInteger('service_id')->unsigned();
            $table->foreign('service_id')->references('id')->on('services');
            $table->bigInteger('employee_id')->unsigned()->nullable();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->bigInteger('value')->unsigned();
            $table->tinyInteger('type')->unsigned()->default(1);
            $table->integer('recurrence_months')->unsigned()->default(1)->nullable();
            $table->tinyInteger('billing_day')->unsigned()->default(1)->nullable();
            $table->tinyInteger('days_to_expire')->unsigned()->default(5)->nullable();
            $table->dateTime('last_billing_date')->nullable();
            $table->string('user_key', 100)->unique();
            $table->string('password_key', 100)->unique();
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
        Schema::dropIfExists('licenses');
    }
}
