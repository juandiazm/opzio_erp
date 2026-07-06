<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimelyPaymentToIncomeLicenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('income_licenses', function (Blueprint $table) {
            $table->date('timely_payment')->nullable()->after('license_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('income_licenses', function (Blueprint $table) {
            $table->dropColumn('timely_payment');
        });
    }
}
