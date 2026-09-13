<?php

use App\Models\jira_connection;
use App\Services\Jira\jira_sync_service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jira_issues', function (Blueprint $table): void {
            if (! Schema::hasColumn('jira_issues', 'description')) {
                $table->longText('description')->nullable()->after('summary');
            }
            if (! Schema::hasColumn('jira_issues', 'comments')) {
                $table->json('comments')->nullable()->after('raw_fields');
            }
        });

        foreach (jira_connection::query()->whereIn('status', ['active', 'draft'])->get() as $connection) {
            try {
                $result = app(jira_sync_service::class)->refreshClientReportData($connection);
                logger()->info('Backfill de fuentes para reportes cliente Jira completado.', [
                    'jira_connection_id' => $connection->id,
                    'updated' => $result['updated'],
                    'failed' => $result['failed'],
                ]);
            } catch (\Throwable $exception) {
                logger()->error('No fue posible completar el backfill de reportes cliente Jira.', [
                    'jira_connection_id' => $connection->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('jira_issues', function (Blueprint $table): void {
            if (Schema::hasColumn('jira_issues', 'comments')) {
                $table->dropColumn('comments');
            }
            if (Schema::hasColumn('jira_issues', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};