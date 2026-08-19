<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationNameToServersProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('servers_projects', function (Blueprint $table) {
            $table->string('notification_name', 255)
                ->nullable()
                ->after('notification_recipients_initialized');
        });
    }

    public function down()
    {
        Schema::table('servers_projects', function (Blueprint $table) {
            $table->dropColumn('notification_name');
        });
    }
}
