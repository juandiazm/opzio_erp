<?php

namespace App\Console\Commands;

use App\Domain\Observability\Models\observability_agent;
use App\Domain\Observability\Models\observability_host;
use Illuminate\Console\Command;

class observability_agent_command extends Command
{
    protected $signature = 'observability:agent
        {agent_id : Stable agent id}
        {host_key : Registered host key}
        {--agent-version= : Initial agent version}
        {--commit-sha= : Initial commit SHA}';

    protected $description = 'Create or update an observability agent';

    public function handle()
    {
        $host = observability_host::where('key', $this->argument('host_key'))->first();
        if (! $host) {
            $this->error('Host not found: '.$this->argument('host_key'));
            return 1;
        }

        $agent = observability_agent::updateOrCreate(
            ['agent_id' => $this->argument('agent_id')],
            [
                'host_id' => $host->id,
                'version' => $this->option('agent-version'),
                'commit_sha' => $this->option('commit-sha'),
                'config_version' => $host->config_version,
                'enabled' => true,
            ]
        );

        $this->info('Observability agent saved: '.$agent->agent_id);

        return 0;
    }
}
