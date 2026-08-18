<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameIncomeGoalMonthsToFrequencyMonths extends Migration
{
    public function up()
    {
        Schema::table('income_goals', function (Blueprint $table) {
            $table->renameColumn('months', 'frequency_months');
        });
    }

    public function down()
    {
        Schema::table('income_goals', function (Blueprint $table) {
            $table->renameColumn('frequency_months', 'months');
        });
    }
}