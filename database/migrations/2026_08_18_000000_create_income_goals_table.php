<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomeGoalsTable extends Migration
{
    public function up()
    {
        Schema::create('income_goals', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_amount', 20, 2);
            $table->unsignedInteger('months');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('income_goals');
    }
}
