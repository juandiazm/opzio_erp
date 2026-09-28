<?php

namespace App\Jobs;

use App\Models\ai_development_execution;
use App\Services\AiDevelopment\ai_development_states;
use App\Services\AiDevelopment\jira_automation_service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class monitor_ai_development_jira_statuses implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 50;

    public function __construct()
    {
        $this->onConnection(config('ai_development.queue_connection', 'database'))
            ->onQueue(config('ai_development.queue_name', 'ai-development'));
    }

    public function uniqueId(): string
    {
        return self::class;
    }

    public function handle(jira_automation_service $service): void
    {
        ai_development_execution::query()
            ->with(['issue.connection', 'issue.project', 'issue.assignee', 'issue.reporter'])
            ->where('status', ai_development_states::WAITING_QUALITY_REVIEW)
            ->whereNotNull('qa_delivered_at')
            ->orderBy('id')
            ->get()
            ->each(function (ai_development_execution $execution) use ($service): void {
                try {
                    if ($execution->issue) {
                        $service->handleSyncedIssue($execution->issue);
                    }
                } catch (Throwable $exception) {
                    logger()->warning('No fue posible revisar el estado Jira local de una ejecucion.', [
                        'execution_id' => $execution->id,
                        'jira_key' => $execution->jira_key,
                        'message' => mb_substr($exception->getMessage(), 0, 500),
                    ]);
                }
            });
    }
}