<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jira_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('site_url', 255);
            $table->string('provider', 40)->default('jira_cloud');
            $table->string('status', 20)->default('draft');
            $table->text('credentials');
            $table->json('settings')->nullable();
            $table->dateTime('last_tested_at')->nullable();
            $table->dateTime('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['provider', 'status'], 'jc_provider_status_idx');
        });

        Schema::create('jira_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->string('mode', 30)->default('manual');
            $table->string('status', 20)->default('running');
            $table->dateTime('cursor_from')->nullable();
            $table->dateTime('cursor_to')->nullable();
            $table->unsignedInteger('records_projects')->default(0);
            $table->unsignedInteger('records_users')->default(0);
            $table->unsignedInteger('records_issues')->default(0);
            $table->unsignedInteger('records_worklogs')->default(0);
            $table->unsignedInteger('records_history')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->index(['jira_connection_id', 'started_at'], 'jsr_conn_started_idx');
        });

        Schema::create('jira_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->string('external_id', 190);
            $table->string('project_key', 80);
            $table->string('name', 255);
            $table->string('project_type', 80)->nullable();
            $table->string('category', 150)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('lead_account_id', 190)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('jira_updated_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['jira_connection_id', 'external_id'], 'jpr_conn_external_unique');
            $table->unique(['jira_connection_id', 'project_key'], 'jpr_conn_key_unique');
            $table->index(['status', 'name'], 'jpr_status_name_idx');
        });

        Schema::create('jira_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->string('account_id', 190);
            $table->string('display_name', 255);
            $table->string('email', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->string('avatar_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['jira_connection_id', 'account_id'], 'ju_conn_account_unique');
            $table->index(['jira_connection_id', 'display_name'], 'ju_conn_display_idx');
        });

        Schema::create('jira_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->foreignId('jira_project_id')->constrained('jira_projects')->cascadeOnDelete();
            $table->string('external_id', 190);
            $table->string('issue_key', 100);
            $table->unsignedBigInteger('parent_jira_issue_id')->nullable();
            $table->unsignedBigInteger('epic_jira_issue_id')->nullable();
            $table->string('issue_type', 100)->nullable();
            $table->string('summary', 500);
            $table->string('status', 120)->nullable();
            $table->string('status_category', 80)->nullable();
            $table->string('priority', 120)->nullable();
            $table->foreignId('assignee_jira_user_id')->nullable()->constrained('jira_users')->nullOnDelete();
            $table->foreignId('reporter_jira_user_id')->nullable()->constrained('jira_users')->nullOnDelete();
            $table->decimal('story_points', 12, 2)->nullable();
            $table->unsignedBigInteger('original_estimate_seconds')->nullable();
            $table->unsignedBigInteger('time_spent_seconds')->nullable();
            $table->dateTime('jira_created_at')->nullable();
            $table->dateTime('jira_updated_at')->nullable();
            $table->dateTime('jira_resolved_at')->nullable();
            $table->date('due_date')->nullable();
            $table->json('labels')->nullable();
            $table->json('components')->nullable();
            $table->json('sprints')->nullable();
            $table->json('raw_fields')->nullable();
            $table->timestamps();
            $table->unique(['jira_connection_id', 'external_id'], 'ji_conn_external_unique');
            $table->unique(['jira_connection_id', 'issue_key'], 'ji_conn_key_unique');
            $table->index(['jira_project_id', 'jira_resolved_at'], 'ji_project_resolved_idx');
            $table->index(['epic_jira_issue_id', 'jira_resolved_at'], 'ji_epic_resolved_idx');
            $table->index(['assignee_jira_user_id', 'jira_resolved_at'], 'ji_assignee_resolved_idx');
            $table->index('parent_jira_issue_id', 'ji_parent_idx');
        });

        Schema::create('jira_issue_worklogs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->foreignId('jira_issue_id')->constrained('jira_issues')->cascadeOnDelete();
            $table->string('external_id', 190);
            $table->foreignId('jira_user_id')->nullable()->constrained('jira_users')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('updated_at_jira')->nullable();
            $table->unsignedBigInteger('time_spent_seconds')->default(0);
            $table->boolean('is_deleted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['jira_connection_id', 'external_id'], 'jiw_conn_external_unique');
            $table->index(['jira_issue_id', 'started_at'], 'jiw_issue_started_idx');
            $table->index(['jira_user_id', 'started_at'], 'jiw_user_started_idx');
        });

        Schema::create('jira_issue_changelogs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->foreignId('jira_issue_id')->constrained('jira_issues')->cascadeOnDelete();
            $table->string('external_history_id', 190);
            $table->foreignId('author_jira_user_id')->nullable()->constrained('jira_users')->nullOnDelete();
            $table->dateTime('changed_at')->nullable();
            $table->string('field', 150);
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['jira_connection_id', 'external_history_id', 'field'], 'jic_conn_history_field_unique');
            $table->index(['jira_issue_id', 'changed_at'], 'jic_issue_changed_idx');
        });

        Schema::create('jira_project_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_project_id')->constrained('jira_projects')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique(['jira_project_id', 'client_id'], 'jpc_project_client_unique');
        });

        Schema::create('jira_project_licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_project_id')->constrained('jira_projects')->cascadeOnDelete();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['jira_project_id', 'license_id'], 'jpl_project_license_unique');
        });

        Schema::create('jira_epic_licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_issue_id')->constrained('jira_issues')->cascadeOnDelete();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->timestamps();
            $table->unique('jira_issue_id');
        });

        Schema::create('jira_user_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_user_id')->constrained('jira_users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('mapping_source', 40)->default('manual');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique('jira_user_id');
        });

        Schema::create('jira_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('jira_connection_id')->nullable()->constrained('jira_connections')->nullOnDelete();
            $table->foreignId('jira_project_id')->nullable()->constrained('jira_projects')->nullOnDelete();
            $table->unsignedBigInteger('jira_epic_issue_id')->nullable();
            $table->string('title', 200);
            $table->string('intention', 100);
            $table->date('from_date');
            $table->date('to_date');
            $table->json('data_sources');
            $table->text('context_prompt')->nullable();
            $table->string('status', 20)->default('generating');
            $table->json('data_snapshot')->nullable();
            $table->json('report_data')->nullable();
            $table->string('ai_model', 120)->nullable();
            $table->string('ai_response_id', 190)->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('last_emailed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
            $table->index(['from_date', 'to_date']);
            $table->index(['jira_project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jira_reports');
        Schema::dropIfExists('jira_user_mappings');
        Schema::dropIfExists('jira_epic_licenses');
        Schema::dropIfExists('jira_project_licenses');
        Schema::dropIfExists('jira_project_clients');
        Schema::dropIfExists('jira_issue_changelogs');
        Schema::dropIfExists('jira_issue_worklogs');
        Schema::dropIfExists('jira_issues');
        Schema::dropIfExists('jira_users');
        Schema::dropIfExists('jira_projects');
        Schema::dropIfExists('jira_sync_runs');
        Schema::dropIfExists('jira_connections');
    }
};
