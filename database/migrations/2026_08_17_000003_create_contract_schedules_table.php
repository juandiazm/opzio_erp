<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('contract_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->foreignId('contract_template_id')->constrained('contract_templates')->restrictOnDelete();
            $table->string('contractable_type', 150);
            $table->unsignedBigInteger('contractable_id')->nullable();
            $table->string('name', 150);
            $table->string('frequency', 20);
            $table->unsignedInteger('interval_value')->default(1);
            $table->dateTime('next_run_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('send_automatically')->default(1);
            $table->boolean('active')->default(1);
            $table->dateTime('last_run_at')->nullable();
            $table->longText('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['contractable_type', 'contractable_id']);
            $table->index(['active', 'next_run_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_schedules');
    }
}