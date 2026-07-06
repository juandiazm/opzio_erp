<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIaTurnsTable extends Migration
{
    public function up()
    {
        Schema::create('ia_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ia_conversations')->onDelete('cascade');
            $table->string('openai_response_id', 100);
            $table->string('parent_response_id', 100)->nullable();
            $table->text('user_input')->nullable();
            $table->json('report_json');
            $table->integer('turn_number')->default(1);
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ia_turns');
    }
}
