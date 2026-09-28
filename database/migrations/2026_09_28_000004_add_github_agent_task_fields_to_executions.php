<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_agents')->update(['provider' => 'github_copilot', 'command' => null]);

        Schema::table('ai_development_executions', function (Blueprint $table): void {
            $table->string('github_task_id', 190)->nullable()->after('main_workflow_run_id');
            $table->string('github_task_url', 1000)->nullable()->after('github_task_id');
            $table->string('github_task_state', 40)->nullable()->after('github_task_url');
            $table->unsignedBigInteger('github_pull_request_number')->nullable()->after('github_task_state');
            $table->index(['github_task_id', 'github_task_state'], 'ade_github_task_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_development_executions', function (Blueprint $table): void {
            $table->dropIndex('ade_github_task_idx');
            $table->dropColumn(['github_task_id', 'github_task_url', 'github_task_state', 'github_pull_request_number']);
        });
    }
};