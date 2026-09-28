<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_development_approvals', 'supervisor_context')) {
            Schema::table('ai_development_approvals', function (Blueprint $table): void {
                $table->text('supervisor_context')->nullable()->after('decision_note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_development_approvals', 'supervisor_context')) {
            Schema::table('ai_development_approvals', function (Blueprint $table): void {
                $table->dropColumn('supervisor_context');
            });
        }
    }
};