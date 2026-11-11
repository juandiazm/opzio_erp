<?php

namespace App\Console\Commands;

use App\Models\jira_connection;
use App\Services\Jira\jira_client;
use App\Services\Jira\jira_sync_service;
use Illuminate\Console\Command;
use Throwable;

class jira_sync extends Command
{
    private const ERROR_RETRY_BACKOFF_MINUTES = 5;

    protected $signature = 'jira:sync {--connection=} {--days=1} {--full} {--incremental}';

    protected $description = 'Sincroniza proyectos, issues, worklogs e historial de Jira';

    public function handle(jira_sync_service $service): int
    {
        if ($this->option('full') && $this->option('incremental')) {
            $this->error('No puedes combinar --full con --incremental.');
            return self::INVALID;
        }

        $connectionQuery = jira_connection::query()
            ->whereIn('status', ['active', 'error'])
            ->when($this->option('connection') === null, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('status', 'active')
                        ->orWhere(function ($query): void {
                            $query->where('status', 'error')
                                ->whereDoesntHave('syncRuns', fn ($runs) => $runs
                                    ->where('status', 'failed')
                                    ->where('finished_at', '>=', now()->subMinutes(self::ERROR_RETRY_BACKOFF_MINUTES)));
                        });
                });
            })
            ->when($this->option('connection') !== null, fn ($query) => $query->whereKey((int) $this->option('connection')));
        $connection = $connectionQuery->first();
        if ($connection === null) {
            $this->warn('No hay conexiones Jira elegibles para sincronizar.');
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
            $message = jira_client::safeMessage($exception);
            $cause = $exception->getPrevious();
            logger()->error('Jira sync command failed.', [
                'connection_id' => $connection->id,
                'connection_name' => $connection->name,
                'exception' => $exception::class,
                'message' => $message,
                'cause_exception' => $cause !== null ? $cause::class : null,
                'cause_message' => $cause !== null ? jira_client::safeDiagnosticMessage($cause, $connection) : null,
            ]);
            $this->error($connection->name.': '.$message);
            return self::FAILURE;
        }

        $this->line('Conexión procesada: '.$connection->name);
        return self::SUCCESS;
    }
}
