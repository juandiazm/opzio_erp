<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourcesAndLicenseToContractsTable extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->json('sources')->nullable()->after('contractable_id');
            $table->foreignId('license_id')->nullable()->after('sources')->constrained('licenses')->nullOnDelete();
            $table->boolean('sync_license_recurrence')->default(false)->after('license_id');
            $table->index(['license_id', 'sync_license_recurrence']);
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['license_id']);
            $table->dropIndex(['license_id', 'sync_license_recurrence']);
            $table->dropColumn(['sources', 'license_id', 'sync_license_recurrence']);
        });
    }
}