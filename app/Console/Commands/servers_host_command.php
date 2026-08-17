<?php

namespace App\Console\Commands;

use App\Domain\Servers\Models\servers_host;
use Illuminate\Console\Command;

class servers_host_command extends Command
{
    protected $signature = 'servers:host
        {key : Stable host key}
        {name : Display name}
        {--hostname= : Hostname reported by the server}
        {--environment=production : Environment name}';

    protected $description = 'Create or update an servers host';

    public function handle()
    {
        $host = servers_host::updateOrCreate(
            ['key' => $this->argument('key')],
            [
                'name' => $this->argument('name'),
                'hostname' => $this->option('hostname'),
                'environment' => $this->option('environment'),
                'enabled' => true,
            ]
        );

        $this->info('Servers host saved: '.$host->key);

        return 0;
    }
}
