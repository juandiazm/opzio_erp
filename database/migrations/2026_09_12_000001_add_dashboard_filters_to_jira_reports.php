<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jira_reports', function (Blueprint $table): void {
            $table->json('jira_project_ids')->nullable();
            $table->json('jira_epic_issue_ids')->nullable();
            $table->json('jira_user_ids')->nullable();
            $table->json('jira_statuses')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jira_reports', function (Blueprint $table): void {
            $table->dropColumn([
                'jira_project_ids',
                'jira_epic_issue_ids',
                'jira_user_ids',
                'jira_statuses',
            ]);
        });
    }
};