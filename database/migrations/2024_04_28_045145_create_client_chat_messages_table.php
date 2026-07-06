<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_chat_id');
            $table->longText('message');
            $table->string('admin_id')->default(0);
            $table->tinyInteger('is_admin');
            $table->boolean('is_read')->default(0);
            $table->timestamps();
            //////
            $table->foreign('client_chat_id')->references('id')->on('client_chats');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_chat_messages');
    }
}
