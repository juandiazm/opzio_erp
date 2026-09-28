<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_development_approval;
use App\Models\ai_development_execution;
use App\Models\jira_automation_project;
use App\traits\mail_trait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class ai_development_notification_service
{
    use mail_trait;

    public function approval(ai_development_approval $approval, string $token): array
    {
        $approval->loadMissing(['issue.connection', 'issue.project', 'issue.assignee', 'issue.reporter', 'project']);
        $recipients = $this->recipients($approval->project);
        if ($recipients->isEmpty()) {
            return ['status' => 0, 'message' => 'No hay supervisores habilitados para recibir la aprobacion.'];
        }

        $snapshot = (array) $approval->snapshot;
        $link = URL::temporarySignedRoute(
            'ai-development.approval',
            $approval->expires_at,
            ['approval' => $approval->id, 'token' => $token],
        );
        $response = $this->SendMail(
            ['subject' => 'Aprobacion requerida: '.($snapshot['jira_key'] ?? $approval->issue?->issue_key)],
            $recipients->all(),
            'mail.ai_development.approval',
            [
                'approval_url' => $link,
                'snapshot' => $snapshot,
                'expires_at' => $approval->expires_at,
            ],
            null,
        );
        $approval->update(['last_notified_at' => now()]);

        return $response;
    }

    public function blocked(ai_development_execution $execution, string $reason): array
    {
        $execution->loadMissing(['issue.project', 'project', 'agent']);
        $recipients = $this->recipients($execution->project);
        if ($recipients->isEmpty()) {
            return ['status' => 0, 'message' => 'No hay supervisores habilitados para recibir el bloqueo.'];
        }

        return $this->SendMail(
            ['subject' => 'Flujo IA bloqueado: '.$execution->jira_key],
            $recipients->all(),
            'mail.ai_development.blocked',
            [
                'execution' => $execution,
                'reason' => $reason,
                'detail_url' => url('/admin/jira?tab=ai-development&execution='.$execution->id),
            ],
            null,
        );
    }

    public function completed(ai_development_execution $execution, array $workflow): array
    {
        $execution->loadMissing(['issue.project', 'project.jiraProject', 'agent']);
        $recipients = $this->recipients($execution->project);
        if ($recipients->isEmpty()) {
            return ['status' => 0, 'message' => 'No hay supervisores habilitados para recibir la finalizacion.'];
        }

        return $this->SendMail(
            ['subject' => 'Flujo IA finalizado en GitHub: '.$execution->jira_key],
            $recipients->all(),
            'mail.ai_development.completed',
            [
                'execution' => $execution,
                'workflow' => $workflow,
                'detail_url' => url('/admin/jira?tab=ai-development&execution='.$execution->id),
            ],
            null,
        );
    }

    public function recipients(jira_automation_project $project): Collection
    {
        $global = \App\Models\jira_automation_supervisor::query()
            ->whereNull('jira_automation_project_id')
            ->where('enabled', true)
            ->get();
        $local = $project->supervisors()->where('enabled', true)->get();

        return $global->concat($local)
            ->map(function ($supervisor): array {
                $email = strtolower(trim((string) ($supervisor->email ?: $supervisor->user?->email)));
                return ['address' => $email, 'name' => trim((string) ($supervisor->name ?: $supervisor->user?->complete_name ?: $email))];
            })
            ->filter(fn (array $recipient): bool => filter_var($recipient['address'], FILTER_VALIDATE_EMAIL) !== false)
            ->unique('address')
            ->values();
    }
}