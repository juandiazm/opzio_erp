<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jira_report_recurrences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('execution_day')->nullable()->after('frequency_unit');
        });
    }

    public function down(): void
    {
        Schema::table('jira_report_recurrences', function (Blueprint $table): void {
            $table->dropColumn('execution_day');
        });
    }
};