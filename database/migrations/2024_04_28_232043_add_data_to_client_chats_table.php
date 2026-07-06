<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDataToClientChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_chats', function (Blueprint $table) {
            $table->string('client_email')->nullable();
            $table->tinyInteger('has_email')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_chats', function (Blueprint $table) {
            $table->dropColumn('client_email');
            $table->dropColumn('has_email');
        });
    }
}
