<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('income_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id')->unsigned();
            $table->foreign('income_id')->references('id')->on('incomes')->onDelete('cascade');
            $table->string('unique_id', 50);
            $table->string('payment_method', 50);
            $table->string('transaction_id', 100)->nullable();
            $table->string('currency', 10);
            $table->float('subtotal', 20, 2);
            $table->float('discount', 20, 2)->default(0);
            $table->float('tax', 20, 2)->default(0);
            $table->float('total', 20, 2);
            $table->tinyInteger('payment_state')->default(0);
            $table->string('payment_reference', 100)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->longText('payment_response')->nullable();
            $table->string('payment_message', 100)->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->bigInteger('client_user_id')->unsigned()->nullable();
            $table->foreign('client_user_id')->references('id')->on('users');
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
        Schema::dropIfExists('income_payments');
    }
}
