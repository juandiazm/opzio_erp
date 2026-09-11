<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jira_projects', function (Blueprint $table): void {
            $table->decimal('story_point_hours_multiplier', 10, 2)->default(1)->after('status');
        });

        Schema::table('jira_issues', function (Blueprint $table): void {
            $table->decimal('estimated_hours', 12, 2)->nullable()->after('story_points');
            $table->boolean('estimated_hours_manual')->default(false)->after('estimated_hours');
        });

        $multipliers = DB::table('jira_projects')->pluck('story_point_hours_multiplier', 'id');
        $storyTypes = ['story', 'user story', 'historia', 'historia de usuario'];

        DB::table('jira_issues')
            ->whereRaw('LOWER(TRIM(issue_type)) IN (?, ?, ?, ?)', $storyTypes)
            ->orderBy('id')
            ->chunkById(500, function ($issues) use ($multipliers): void {
                foreach ($issues as $issue) {
                    if ($issue->story_points === null) {
                        continue;
                    }

                    $multiplier = (float) ($multipliers[$issue->jira_project_id] ?? 1);
                    DB::table('jira_issues')
                        ->where('id', $issue->id)
                        ->update([
                            'estimated_hours' => round((float) $issue->story_points * $multiplier, 2),
                            'estimated_hours_manual' => false,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('jira_issues', function (Blueprint $table): void {
            $table->dropColumn(['estimated_hours', 'estimated_hours_manual']);
        });

        Schema::table('jira_projects', function (Blueprint $table): void {
            $table->dropColumn('story_point_hours_multiplier');
        });
    }
};