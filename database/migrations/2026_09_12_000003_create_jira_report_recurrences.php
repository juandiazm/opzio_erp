<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jira_report_recurrences', function (Blueprint $table): void {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->foreignId('template_report_id')->constrained('jira_reports')->cascadeOnDelete();
            $table->unsignedSmallInteger('frequency_value');
            $table->string('frequency_unit', 10);
            $table->unsignedSmallInteger('range_value');
            $table->string('range_unit', 10);
            $table->dateTime('next_run_at');
            $table->dateTime('last_run_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'next_run_at']);
        });

        Schema::table('jira_reports', function (Blueprint $table): void {
            $table->foreignId('recurrence_id')->nullable()->after('last_emailed_at')->constrained('jira_report_recurrences')->nullOnDelete();
            $table->unsignedInteger('recurrence_sequence')->default(0)->after('recurrence_id');
            $table->index('recurrence_id');
        });
    }

    public function down(): void
    {
        Schema::table('jira_reports', function (Blueprint $table): void {
            $table->dropForeign(['recurrence_id']);
            $table->dropIndex(['recurrence_id']);
            $table->dropColumn(['recurrence_id', 'recurrence_sequence']);
        });

        Schema::dropIfExists('jira_report_recurrences');
    }
};