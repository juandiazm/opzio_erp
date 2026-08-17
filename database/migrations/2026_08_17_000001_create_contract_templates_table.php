<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('subject', 200);
            $table->longText('content');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['contract_type_id', 'active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_templates');
    }
}