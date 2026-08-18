<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateRangeToIncomeGoalsTable extends Migration
{
    public function up()
    {
        Schema::table('income_goals', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('frequency_months');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down()
    {
        Schema::table('income_goals', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
}