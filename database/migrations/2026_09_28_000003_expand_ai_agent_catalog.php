<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_agents', 'description')) {
            Schema::table('ai_agents', function (Blueprint $table): void {
                $table->string('description', 255)->nullable()->after('model');
                $table->string('cost_tier', 20)->default('medium')->after('description');
            });
        }

        DB::table('ai_agents')->where('model', 'luna')->update(['model' => 'gpt-5.6-luna']);
        $now = now();
        $catalog = [
            [
                'name' => 'Luna',
                'provider' => 'github_copilot',
                'model' => 'gpt-5.6-luna',
                'description' => 'Rapido y economico para cambios rutinarios.',
                'cost_tier' => 'low',
                'enabled' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Terra',
                'provider' => 'command',
                'model' => 'gpt-5.6-terra',
                'description' => 'Equilibrio para historias de complejidad media.',
                'cost_tier' => 'medium',
                'enabled' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Sol',
                'provider' => 'command',
                'model' => 'gpt-5.6-sol',
                'description' => 'Razonamiento profundo para cambios complejos.',
                'cost_tier' => 'high',
                'enabled' => true,
                'is_default' => false,
            ],
        ];

        foreach ($catalog as $agent) {
            DB::table('ai_agents')->updateOrInsert(
                ['name' => $agent['name']],
                [...$agent, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        DB::table('ai_agents')->where('name', '<>', 'Luna')->update(['is_default' => false]);
    }

    public function down(): void
    {
        DB::table('ai_agents')->whereIn('name', ['Terra', 'Sol'])->delete();
        Schema::table('ai_agents', function (Blueprint $table): void {
            $table->dropColumn(['description', 'cost_tier']);
        });
    }
};