<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationDeliveryFieldsToMailLogs extends Migration
{
    public function up()
    {
        $columns = [];
        if (!Schema::hasColumn('mail_logs', 'send_at')) {
            $columns[] = 'send_at';
        }
        if (!Schema::hasColumn('mail_logs', 'notification_batch')) {
            $columns[] = 'notification_batch';
        }

        if (!$columns) {
            return;
        }

        Schema::table('mail_logs', function (Blueprint $table) use ($columns) {
            if (in_array('send_at', $columns, true)) {
                $table->dateTime('send_at')->nullable()->after('status')->index();
            }
            if (in_array('notification_batch', $columns, true)) {
                $table->string('notification_batch', 100)->nullable()->after('send_at')->index();
            }
        });
    }

    public function down()
    {
        $columns = [];
        if (Schema::hasColumn('mail_logs', 'notification_batch')) {
            $columns[] = 'notification_batch';
        }
        if (Schema::hasColumn('mail_logs', 'send_at')) {
            $columns[] = 'send_at';
        }

        if ($columns) {
            Schema::table('mail_logs', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}