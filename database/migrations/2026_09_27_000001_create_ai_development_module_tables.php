<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
                $table->index(['queue', 'reserved_at', 'available_at']);
            });
        }

        Schema::create('github_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('singleton_key')->default(1)->unique();
            $table->string('name', 150);
            $table->string('base_url', 255)->default('https://api.github.com');
            $table->string('status', 20)->default('draft');
            $table->text('credentials');
            $table->json('settings')->nullable();
            $table->dateTime('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_agents', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('provider', 80);
            $table->string('model', 160);
            $table->text('command')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['enabled', 'is_default']);
        });

        DB::table('ai_agents')->insert([
            'name' => 'Luna',
            'provider' => 'command',
            'model' => 'luna',
            'command' => null,
            'enabled' => true,
            'is_default' => true,
            'settings' => json_encode(['seeded' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('jira_automation_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_project_id')->unique()->constrained('jira_projects')->cascadeOnDelete();
            $table->foreignId('github_connection_id')->nullable()->constrained('github_connections')->nullOnDelete();
            $table->string('github_owner', 120)->nullable();
            $table->string('github_repository', 200)->nullable();
            $table->foreignId('default_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('base_branch', 200)->default('qa');
            $table->unsignedSmallInteger('max_execution_attempts')->default(5);
            $table->unsignedTinyInteger('max_ci_attempts')->default(3);
            $table->unsignedInteger('max_execution_minutes')->default(120);
            $table->unsignedTinyInteger('max_consecutive_failures')->default(3);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['enabled', 'github_repository']);
        });

        Schema::create('jira_automation_issue_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_automation_project_id')->constrained('jira_automation_projects')->cascadeOnDelete();
            $table->string('issue_type', 120);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->unique(['jira_automation_project_id', 'issue_type'], 'jai_project_type_unique');
        });

        Schema::create('jira_automation_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_automation_project_id')->constrained('jira_automation_projects')->cascadeOnDelete();
            $table->foreignId('jira_user_id')->nullable()->constrained('jira_users')->nullOnDelete();
            $table->string('assignee_key', 190);
            $table->string('display_name', 255)->nullable();
            $table->boolean('is_unassigned')->default(false);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->unique(['jira_automation_project_id', 'assignee_key'], 'jaa_project_assignee_unique');
        });

        Schema::create('jira_automation_supervisors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_automation_project_id')->nullable()->constrained('jira_automation_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 200)->nullable();
            $table->string('email', 255);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['jira_automation_project_id', 'enabled'], 'jas_project_enabled_idx');
            $table->index(['email', 'enabled'], 'jas_email_enabled_idx');
        });

        Schema::create('ai_development_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_issue_id')->constrained('jira_issues')->cascadeOnDelete();
            $table->foreignId('jira_automation_project_id')->constrained('jira_automation_projects')->cascadeOnDelete();
            $table->string('source_fingerprint', 128);
            $table->string('token_hash', 128)->unique();
            $table->string('status', 30)->default('pending');
            $table->json('snapshot');
            $table->decimal('story_point_estimate', 12, 2)->nullable();
            $table->foreignId('selected_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('expires_at');
            $table->text('decision_note')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->dateTime('last_notified_at')->nullable();
            $table->timestamps();
            $table->unique(['jira_issue_id', 'source_fingerprint'], 'ada_issue_fingerprint_unique');
            $table->index(['status', 'expires_at'], 'ada_status_expiry_idx');
        });

        Schema::create('ai_development_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jira_issue_id')->constrained('jira_issues')->cascadeOnDelete();
            $table->foreignId('jira_automation_project_id')->constrained('jira_automation_projects')->cascadeOnDelete();
            $table->foreignId('approval_id')->nullable()->constrained('ai_development_approvals')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->string('jira_key', 100);
            $table->string('repository', 320)->nullable();
            $table->string('status', 40)->default('candidate');
            $table->string('current_phase', 80)->default('candidate');
            $table->string('feature_branch', 255)->nullable();
            $table->string('base_branch', 200)->default('qa');
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->unsignedTinyInteger('ci_attempts')->default(0);
            $table->unsignedTinyInteger('main_ci_attempts')->default(0);
            $table->unsignedTinyInteger('consecutive_failures')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('qa_delivered_at')->nullable();
            $table->dateTime('qa_last_feedback_at')->nullable();
            $table->dateTime('done_detected_at')->nullable();
            $table->string('last_commit_sha', 80)->nullable();
            $table->string('qa_workflow_run_id', 100)->nullable();
            $table->string('main_workflow_run_id', 100)->nullable();
            $table->string('workspace_path', 1000)->nullable();
            $table->json('context')->nullable();
            $table->text('error')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamps();
            $table->index(['jira_issue_id', 'status'], 'ade_issue_status_idx');
            $table->index(['status', 'last_activity_at'], 'ade_status_activity_idx');
        });

        Schema::create('ai_development_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('execution_id')->nullable()->constrained('ai_development_executions')->cascadeOnDelete();
            $table->foreignId('approval_id')->nullable()->constrained('ai_development_approvals')->cascadeOnDelete();
            $table->string('event', 100);
            $table->string('phase', 80)->nullable();
            $table->string('actor_type', 80)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedSmallInteger('attempt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['execution_id', 'created_at'], 'ade_event_execution_idx');
            $table->index(['event', 'created_at'], 'ade_event_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_development_events');
        Schema::dropIfExists('ai_development_executions');
        Schema::dropIfExists('ai_development_approvals');
        Schema::dropIfExists('jira_automation_supervisors');
        Schema::dropIfExists('jira_automation_assignees');
        Schema::dropIfExists('jira_automation_issue_types');
        Schema::dropIfExists('jira_automation_projects');
        Schema::dropIfExists('ai_agents');
        Schema::dropIfExists('github_connections');
    }
};