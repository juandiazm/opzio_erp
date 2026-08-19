<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NormalizeServerProjectNotificationSources extends Migration
{
    public function up()
    {
        DB::table('servers_project_notifications')
            ->where('source_type', '<>', 'project')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($recipient) {
                DB::table('servers_project_notifications')
                    ->where('id', $recipient->id)
                    ->update([
                        'source_type' => 'project',
                        'source_id' => null,
                        'source_key' => 'project:'.Str::uuid()->toString(),
                    ]);
            });
    }

    public function down()
    {
    }
}
