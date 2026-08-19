<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillInitializedServerProjectNotifications extends Migration
{
    public function up()
    {
        DB::table('servers_projects')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('servers_project_notifications')
                    ->whereColumn('servers_project_notifications.project_id', 'servers_projects.id');
            })
            ->update([
                'notification_recipients_initialized' => true,
            ]);
    }

    public function down()
    {
        DB::table('servers_projects')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('servers_project_notifications')
                    ->whereColumn('servers_project_notifications.project_id', 'servers_projects.id');
            })
            ->update([
                'notification_recipients_initialized' => false,
            ]);
    }
}
