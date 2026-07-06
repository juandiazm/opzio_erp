<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIaResponseFlagToClientChat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_chats', function (Blueprint $table) {
            $table->boolean('ia_response')->default(1)->after('has_email');
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
            $table->dropColumn('ia_response');
        });
    }
}
