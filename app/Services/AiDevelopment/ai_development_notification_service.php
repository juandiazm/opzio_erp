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
        $supervisorResponse = ['status' => 0, 'message' => 'No hay supervisores habilitados para recibir el bloqueo.'];
        if ($recipients->isNotEmpty()) {
            $supervisorResponse = $this->SendMail(
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

        $reporterResponse = $this->reporterFlow(
            $execution,
            'Flujo bloqueado',
            'La ejecucion se detuvo y requiere una revision del equipo responsable.',
            ['Motivo' => $reason],
        );

        return array_merge($supervisorResponse, [
            'reporter_status' => $reporterResponse['status'] ?? 0,
            'reporter_message' => $reporterResponse['message'] ?? '',
        ]);
    }

    public function qaAvailable(ai_development_execution $execution): array
    {
        $execution->loadMissing(['issue.connection', 'issue.project', 'issue.reporter.mapping.user', 'project', 'agent']);
        $reporter = $execution->issue?->reporter;
        $email = strtolower(trim((string) ($reporter?->email ?: $reporter?->mapping?->user?->email ?: '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 0, 'message' => 'El reporter no tiene un correo electronico valido para notificar la revision QA.'];
        }

        $issueKey = (string) ($execution->jira_key ?: $execution->issue?->issue_key);
        $siteUrl = trim((string) ($execution->issue?->connection?->site_url ?? ''));
        $issueUrl = filled($siteUrl) && filled($issueKey)
            ? rtrim($siteUrl, '/').'/browse/'.rawurlencode($issueKey)
            : null;

        return $this->SendMail(
            ['subject' => 'Revision QA requerida: '.$issueKey],
            [['address' => $email, 'name' => trim((string) ($reporter->display_name ?: $email))]],
            'mail.ai_development.qa_review',
            [
                'execution' => $execution,
                'issue' => $execution->issue,
                'reporter' => $reporter,
                'issue_url' => $issueUrl,
            ],
            null,
        );
    }

    public function reporterFlow(
        ai_development_execution $execution,
        string $title,
        string $message,
        array $details = [],
    ): array {
        $execution->loadMissing(['issue.connection', 'issue.project', 'issue.reporter.mapping.user', 'project', 'agent']);
        $reporter = $execution->issue?->reporter;
        $email = strtolower(trim((string) ($reporter?->email ?: $reporter?->mapping?->user?->email ?: '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 0, 'message' => 'El reporter no tiene un correo electronico valido para notificar el estado de la ejecucion.'];
        }

        $issueKey = (string) ($execution->jira_key ?: $execution->issue?->issue_key);
        $siteUrl = trim((string) ($execution->issue?->connection?->site_url ?? ''));
        $issueUrl = filled($siteUrl) && filled($issueKey)
            ? rtrim($siteUrl, '/').'/browse/'.rawurlencode($issueKey)
            : null;

        return $this->SendMail(
            ['subject' => $title.': '.$issueKey],
            [['address' => $email, 'name' => trim((string) ($reporter->display_name ?: $email))]],
            'mail.ai_development.reporter',
            [
                'execution' => $execution,
                'issue' => $execution->issue,
                'reporter' => $reporter,
                'title' => $title,
                'message' => $message,
                'details' => $details,
                'issue_url' => $issueUrl,
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