<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomeLicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('income_licenses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id')->unsigned();
            $table->foreign('income_id')->references('id')->on('incomes');
            $table->bigInteger('license_id')->unsigned();
            $table->foreign('license_id')->references('id')->on('licenses');
            $table->string('license_name', 150);
            $table->bigInteger('service_id')->unsigned();
            $table->foreign('service_id')->references('id')->on('services');
            $table->string('service_name', 150);
            $table->integer('recurrence_months')->nullable();
            $table->decimal('value', 20, 2);
            $table->decimal('comission', 20, 2)->nullable();
            $table->bigInteger('employee_id')->unsigned()->nullable();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->string('employee_name', 150)->nullable();
            $table->bigInteger('tax_id')->unsigned()->nullable();
            $table->foreign('tax_id')->references('id')->on('taxes')->nullable();
            $table->string('tax_name', 150)->nullable();
            $table->decimal('tax_value', 10, 2)->nullable();
            $table->longText('description')->nullable();
            $table->decimal('total', 20, 2);
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
        Schema::dropIfExists('income_licenses');
    }
}
