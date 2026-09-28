<?php

namespace App\Console\Commands;

use App\Models\ai_development_approval;
use App\Models\ai_development_execution;
use App\Models\jira_automation_project;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\jira_automation_service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class jira_automation_scan extends Command
{
    protected $signature = 'jira:automation:scan {--project=} {--expire : Expira solicitudes de aprobacion vencidas}';

    protected $description = 'Detecta candidatas Jira y expira aprobaciones vencidas';

    public function handle(jira_automation_service $service, ai_development_state_machine $states): int
    {
        if (! Schema::hasTable('jira_automation_projects')) {
            $this->warn('Las migraciones de automatizacion Jira aun no estan aplicadas.');
            return self::SUCCESS;
        }
        $projects = jira_automation_project::query()
            ->where('enabled', true)
            ->when($this->option('project'), fn ($query, $id) => $query->whereKey((int) $id))
            ->get();
        $detected = 0;
        foreach ($projects as $project) {
            $detected += $service->scanProject($project);
        }
        $this->info('Candidatas detectadas: '.$detected.'.');

        if ($this->option('expire')) {
            $expired = ai_development_approval::query()
                ->where('status', 'pending')
                ->where('expires_at', '<', now())
                ->get();
            foreach ($expired as $approval) {
                $approval->update(['status' => 'expired']);
                $execution = ai_development_execution::query()->where('approval_id', $approval->id)->where('status', 'awaiting_approval')->first();
                if ($execution) {
                    $states->block($execution, 'La solicitud de aprobacion expiro sin decision.');
                }
            }
            $this->info('Solicitudes expiradas: '.$expired->count().'.');
        }

        return self::SUCCESS;
    }
}