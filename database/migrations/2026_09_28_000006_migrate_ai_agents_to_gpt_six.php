<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $agents = DB::table('ai_agents')
            ->whereIn('model', ['gpt-5.6-luna', 'gpt-5.6-terra', 'gpt-5.6-sol'])
            ->get(['id', 'model', 'settings']);

        foreach ($agents as $agent) {
            $settings = json_decode((string) $agent->settings, true);
            if (is_array($settings) && isset($settings['model_family'])) {
                $settings['model_family'] = str_replace('gpt-5.6', 'gpt-6', (string) $settings['model_family']);
            }

            DB::table('ai_agents')->where('id', $agent->id)->update([
                'model' => str_replace('gpt-5.6', 'gpt-6', (string) $agent->model),
                'settings' => is_array($settings) ? json_encode($settings) : $agent->settings,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $agents = DB::table('ai_agents')
            ->whereIn('model', ['gpt-6-luna', 'gpt-6-terra', 'gpt-6-sol'])
            ->get(['id', 'model', 'settings']);

        foreach ($agents as $agent) {
            $settings = json_decode((string) $agent->settings, true);
            if (is_array($settings) && isset($settings['model_family'])) {
                $settings['model_family'] = str_replace('gpt-6', 'gpt-5.6', (string) $settings['model_family']);
            }

            DB::table('ai_agents')->where('id', $agent->id)->update([
                'model' => str_replace('gpt-6', 'gpt-5.6', (string) $agent->model),
                'settings' => is_array($settings) ? json_encode($settings) : $agent->settings,
                'updated_at' => now(),
            ]);
        }
    }
};
