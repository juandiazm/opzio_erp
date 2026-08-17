<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->foreignId('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->foreignId('contract_template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            $table->string('contractable_type', 150);
            $table->unsignedBigInteger('contractable_id');
            $table->string('name', 200);
            $table->string('subject', 200);
            $table->longText('content')->nullable();
            $table->string('status', 30)->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->longText('notes')->nullable();
            $table->json('generation_data')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->dateTime('scheduled_for')->nullable();
            $table->string('schedule_key', 150)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['contractable_type', 'contractable_id']);
            $table->index(['status', 'contract_type_id']);
            $table->index('scheduled_for');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}