<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHiringDataToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date("entry_date")->nullable();
            $table->tinyInteger("payment_type")->nullable();
            $table->string("bank", 100)->nullable();
            $table->string("account_number", 100)->nullable();
            $table->string("account_type", 100)->nullable();
            $table->bigInteger("salary")->nullable();
            $table->string("contract", 100)->nullable();
            $table->bigInteger("department_id")->unsigned()->nullable();
            $table->string("charge", 100)->nullable();
            $table->bigInteger("eps_id")->unsigned()->nullable();
            $table->bigInteger("afp_id")->unsigned()->nullable();
            $table->bigInteger("arl_id")->unsigned()->nullable();
            $table->date("retirement_date")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table-dropColumn("entry_date");
            $table-dropColumn("payment_type");
            $table-dropColumn("bank");
            $table-dropColumn("account_number");
            $table-dropColumn("account_type");
            $table-dropColumn("salary");
            $table-dropColumn("contract");
            $table-dropColumn("department_id");
            $table-dropColumn("charge");
            $table-dropColumn("eps_id");
            $table-dropColumn("afp_id");
            $table-dropColumn("arl_id");
            $table-dropColumn("retirement_date");
        });
    }
}
