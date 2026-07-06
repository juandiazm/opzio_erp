<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToIaConversationsTable extends Migration
{
    public function up()
    {
        Schema::table('ia_conversations', function (Blueprint $table) {
            $table->string('status', 20)->default('completed')->after('report_period');
        });
    }

    public function down()
    {
        Schema::table('ia_conversations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
