<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_agents')->updateOrInsert(
            ['name' => 'Luna'],
            [
                'provider' => 'command',
                'model' => 'gpt-5.6-luna',
                'enabled' => true,
                'is_default' => true,
                'settings' => json_encode(['product' => 'Luna', 'model_family' => 'gpt-5.6']),
                'updated_at' => now(),
            ],
        );

        DB::table('ai_agents')
            ->where('name', '<>', 'Luna')
            ->update(['is_default' => false]);
    }

    public function down(): void
    {
        DB::table('ai_agents')
            ->where('name', 'Luna')
            ->update(['model' => 'luna', 'settings' => json_encode(['seeded' => true])]);
    }
};