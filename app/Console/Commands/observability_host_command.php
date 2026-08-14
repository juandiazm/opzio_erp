<?php

namespace App\Console\Commands;

use App\Domain\Observability\Models\observability_host;
use Illuminate\Console\Command;

class observability_host_command extends Command
{
    protected $signature = 'observability:host
        {key : Stable host key}
        {name : Display name}
        {--hostname= : Hostname reported by the server}
        {--environment=production : Environment name}';

    protected $description = 'Create or update an observability host';

    public function handle()
    {
        $host = observability_host::updateOrCreate(
            ['key' => $this->argument('key')],
            [
                'name' => $this->argument('name'),
                'hostname' => $this->option('hostname'),
                'environment' => $this->option('environment'),
                'enabled' => true,
            ]
        );

        $this->info('Observability host saved: '.$host->key);

        return 0;
    }
}
