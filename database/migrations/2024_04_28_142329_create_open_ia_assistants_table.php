<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenIaAssistantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('open_ia_assistants', function (Blueprint $table) {
            $table->id();
            $table->string('assistant_id',200);
            $table->string('object')->nullable();
            $table->string('name')->nullable();
            $table->string('createdAt')->nullable();
            $table->string('description')->nullable();
            $table->string('model')->nullable();
            $table->longText('instructions')->nullable();
            $table->longText('tools')->nullable();
            $table->longText('file_ids')->nullable();
            $table->longText('metadata')->nullable();
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
        Schema::dropIfExists('open_ia_assistants');
    }
}
