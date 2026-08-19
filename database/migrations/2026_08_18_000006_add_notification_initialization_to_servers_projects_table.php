<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationInitializationToServersProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('servers_projects', function (Blueprint $table) {
            $table->boolean('notification_recipients_initialized')
                ->default(false)
                ->after('notifications_enabled');
        });
    }

    public function down()
    {
        Schema::table('servers_projects', function (Blueprint $table) {
            $table->dropColumn('notification_recipients_initialized');
        });
    }
}
