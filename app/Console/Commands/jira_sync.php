<?php

namespace App\Console\Commands;

use App\Models\jira_connection;
use App\Services\Jira\jira_sync_service;
use Illuminate\Console\Command;
use Throwable;

class jira_sync extends Command
{
    protected $signature = 'jira:sync {--connection=} {--days=1} {--full} {--incremental}';

    protected $description = 'Sincroniza proyectos, issues, worklogs e historial de Jira';

    public function handle(jira_sync_service $service): int
    {
        if ($this->option('full') && $this->option('incremental')) {
            $this->error('No puedes combinar --full con --incremental.');
            return self::INVALID;
        }

        $connection = jira_connection::query()
            ->where('status', 'active')
            ->when($this->option('connection') !== null, fn ($query) => $query->whereKey((int) $this->option('connection')))
            ->first();
        if ($connection === null) {
            $this->warn('No hay conexiones Jira para sincronizar.');
            return self::SUCCESS;
        }

        try {
            $result = $service->sync(
                $connection,
                (int) $this->option('days'),
                (bool) $this->option('full'),
                (bool) $this->option('incremental'),
            );
            $this->info($connection->name.': '.$result['message']);
        } catch (Throwable $exception) {
            $this->error($connection->name.': '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->line('Conexión procesada: '.$connection->name);
        return self::SUCCESS;
    }
}
