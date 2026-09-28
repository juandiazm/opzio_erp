<?php

namespace App\Providers;

use App\Services\AiDevelopment\ai_agent_provider_interface;
use App\Services\AiDevelopment\github_copilot_agent_provider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ai_agent_provider_interface::class, github_copilot_agent_provider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
