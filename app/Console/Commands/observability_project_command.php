<?php

namespace App\Console\Commands;

use App\Domain\Observability\Models\observability_host;
use App\Domain\Observability\Models\observability_project;
use Illuminate\Console\Command;

class observability_project_command extends Command
{
    protected $signature = 'observability:project
        {host_key : Registered host key}
        {key : Stable project key}
        {name : Display name}
        {path : Absolute project path on the server}
        {--environment=production : Environment name}
        {--php-version= : PHP version}
        {--fpm-pool= : PHP-FPM pool name}
        {--fpm-status-url= : Local PHP-FPM status URL}
        {--nginx-access-log= : JSON NGINX access log path}
        {--nginx-error-log= : NGINX error log path}
        {--attribution-mode=approximate : approximate, pool or cgroup}';

    protected $description = 'Create or update an observability project';

    public function handle()
    {
        $host = observability_host::where('key', $this->argument('host_key'))->first();
        if (! $host) {
            $this->error('Host not found: '.$this->argument('host_key'));
            return 1;
        }

        $mode = $this->option('attribution-mode');
        if (! in_array($mode, ['approximate', 'pool', 'cgroup'], true)) {
            $this->error('Invalid attribution mode: '.$mode);
            return 1;
        }

        $project = observability_project::updateOrCreate(
            ['host_id' => $host->id, 'key' => $this->argument('key')],
            [
                'name' => $this->argument('name'),
                'path' => $this->argument('path'),
                'environment' => $this->option('environment'),
                'php_version' => $this->option('php-version'),
                'fpm_pool' => $this->option('fpm-pool'),
                'fpm_status_url' => $this->option('fpm-status-url'),
                'nginx_access_log' => $this->option('nginx-access-log'),
                'nginx_error_log' => $this->option('nginx-error-log'),
                'attribution_mode' => $mode,
                'enabled' => true,
            ]
        );

        $host->increment('config_version');
        $this->info('Observability project saved: '.$project->key);

        return 0;
    }
}
