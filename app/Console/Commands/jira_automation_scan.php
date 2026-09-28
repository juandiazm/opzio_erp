<?php

namespace App\Console\Commands;

use App\Models\ai_development_approval;
use App\Models\ai_development_execution;
use App\Models\jira_automation_project;
use App\Models\jira_issue;
use App\Services\AiDevelopment\ai_development_state_machine;
use App\Services\AiDevelopment\jira_automation_service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class jira_automation_scan extends Command
{
    protected $signature = 'jira:automation:scan {--project=} {--issue=} {--explain : Explica por que una HU no es candidata} {--retry-blocked : Reabre una aprobacion bloqueada de forma explicita} {--expire : Expira solicitudes de aprobacion vencidas}';

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
        if ($this->option('issue')) {
            $issue = jira_issue::query()
                ->with(['assignee', 'reporter', 'project', 'connection'])
                ->where('issue_key', (string) $this->option('issue'))
                ->first();
            if (! $issue) {
                $this->error('No se encontro la HU '.$this->option('issue').' en jira_issues.');
                return self::FAILURE;
            }
            $project = $projects->firstWhere('jira_project_id', $issue->jira_project_id)
                ?: jira_automation_project::query()->with(['issueTypes', 'assignees'])->where('jira_project_id', $issue->jira_project_id)->first();
            if (! $project) {
                $this->error('No existe configuracion de automatizacion para el proyecto Jira '.$issue->jira_project_id.'.');
                return self::FAILURE;
            }
            if ($this->option('retry-blocked')) {
                $reasons = $service->candidateReasons($issue, $project, false);
                if ($reasons !== []) {
                    $this->warn($issue->issue_key.': no se puede reintentar.');
                    foreach ($reasons as $reason) {
                        $this->line('- '.$reason);
                    }
                    return self::SUCCESS;
                }
                $approval = $service->retryBlockedIssue($issue);
                if (! $approval) {
                    $this->warn($issue->issue_key.': no existe una aprobacion bloqueada reintentable.');
                } else {
                    $this->info($issue->issue_key.': aprobacion bloqueada reabierta y notificada.');
                }
                return self::SUCCESS;
            }
            $reasons = $service->candidateReasons($issue, $project);
            if ($reasons === []) {
                $this->info($issue->issue_key.': candidata valida.');
                if ($this->option('explain')) {
                    $this->line('Estado: '.$issue->status.' | Tipo: '.$issue->issue_type.' | Assignee: '.($issue->assignee?->display_name ?: 'Unassigned').' ('.($issue->assignee?->account_id ?: '__unassigned__').')');
                }
            } else {
                $this->warn($issue->issue_key.': no es candidata.');
                foreach ($reasons as $reason) {
                    $this->line('- '.$reason);
                }
            }
            return self::SUCCESS;
        }
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