<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jira_connections', function (Blueprint $table): void {
            $table->unsignedTinyInteger('singleton_key')->nullable()->after('id');
        });

        $connectionCount = DB::table('jira_connections')->count();
        if ($connectionCount > 1) {
            throw new \RuntimeException('Jira solo admite una conexión. Consolida las filas de jira_connections antes de ejecutar esta migración.');
        }

        DB::table('jira_connections')->update(['singleton_key' => 1]);

        Schema::table('jira_connections', function (Blueprint $table): void {
            $table->unique('singleton_key', 'jira_connections_singleton_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jira_connections', function (Blueprint $table): void {
            $table->dropUnique('jira_connections_singleton_unique');
            $table->dropColumn('singleton_key');
        });
    }
};
