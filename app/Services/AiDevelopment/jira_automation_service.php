<?php

namespace App\Services\AiDevelopment;

use App\Jobs\promote_ai_development_execution;
use App\Jobs\run_ai_development_execution;
use App\Jobs\monitor_ai_development_agent;
use App\Models\ai_agent;
use App\Models\ai_development_approval;
use App\Models\ai_development_event;
use App\Models\ai_development_execution;
use App\Models\jira_automation_assignee;
use App\Models\jira_automation_project;
use App\Models\jira_automation_supervisor;
use App\Models\jira_issue;
use App\Models\jira_project;
use App\Services\Jira\jira_client;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class jira_automation_service
{
    public function __construct(
        private readonly ai_development_notification_service $notifications,
        private readonly ai_development_state_machine $states,
    ) {
    }

    public function detectCandidate(jira_issue $issue): ?ai_development_approval
    {
        $configuration = jira_automation_project::query()
            ->with(['jiraProject.connection', 'issueTypes', 'assignees', 'defaultAgent'])
            ->where('jira_project_id', $issue->jira_project_id)
            ->where('enabled', true)
            ->first();
        if (! $configuration) {
            return null;
        }
        $reasons = $this->candidateReasons($issue, $configuration, false, false);
        if ($reasons !== [] || (! jira_automation_statuses::isPending($issue->status) && ! jira_automation_statuses::isDevelopmentActive($issue->status))) {
            return null;
        }

        $fingerprint = $this->fingerprint($issue, $configuration);
        if (ai_development_approval::query()->where('jira_issue_id', $issue->id)->where('source_fingerprint', $fingerprint)->exists()) {
            return ai_development_approval::query()->where('jira_issue_id', $issue->id)->where('source_fingerprint', $fingerprint)->first();
        }
        if ($this->hasEquivalentPendingWork($issue)) {
            return null;
        }

        $token = Str::random(64);
        try {
            [$approval, $execution] = DB::transaction(function () use ($issue, $configuration, $fingerprint, $token): array {
                $approval = ai_development_approval::create([
                    'jira_issue_id' => $issue->id,
                    'jira_automation_project_id' => $configuration->id,
                    'source_fingerprint' => $fingerprint,
                    'token_hash' => hash('sha256', $token),
                    'status' => 'pending',
                    'snapshot' => $this->snapshot($issue, $configuration),
                    'expires_at' => now()->addHours(max(1, (int) config('ai_development.approval_ttl_hours', 48))),
                ]);
                $execution = ai_development_execution::create([
                    'jira_issue_id' => $issue->id,
                    'jira_automation_project_id' => $configuration->id,
                    'approval_id' => $approval->id,
                    'agent_id' => $configuration->default_agent_id,
                    'jira_key' => $issue->issue_key,
                    'repository' => $this->repository($configuration),
                    'status' => ai_development_states::AWAITING_APPROVAL,
                    'current_phase' => ai_development_states::AWAITING_APPROVAL,
                    'base_branch' => $configuration->base_branch ?: config('ai_development.default_base_branch', 'qa'),
                    'last_activity_at' => now(),
                ]);
                ai_development_event::create([
                    'execution_id' => $execution->id,
                    'approval_id' => $approval->id,
                    'event' => 'candidate_detected',
                    'phase' => ai_development_states::CANDIDATE,
                    'metadata' => ['fingerprint' => $fingerprint],
                ]);

                return [$approval, $execution];
            });
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw $exception;
            }

            return ai_development_approval::query()
                ->where('jira_issue_id', $issue->id)
                ->where('source_fingerprint', $fingerprint)
                ->first();
        }

        $notification = $this->notifications->approval($approval, $token);
        ai_development_event::create([
            'execution_id' => $execution->id,
            'approval_id' => $approval->id,
            'event' => ($notification['status'] ?? 0) === 1 ? 'approval_sent' : 'approval_notification_failed',
            'phase' => ai_development_states::AWAITING_APPROVAL,
            'metadata' => ['message' => mb_substr((string) ($notification['message'] ?? ''), 0, 500)],
        ]);

        return $approval->fresh(['issue', 'project', 'execution']);
    }

    public function retryBlockedIssue(jira_issue $issue): ?ai_development_approval
    {
        $configuration = jira_automation_project::query()
            ->with(['jiraProject.connection', 'issueTypes', 'assignees', 'defaultAgent'])
            ->where('jira_project_id', $issue->jira_project_id)
            ->where('enabled', true)
            ->first();
        if (! $configuration || ! $this->isCandidate($issue, $configuration)) {
            return null;
        }

        $approval = ai_development_approval::query()
            ->with('execution')
            ->where('jira_issue_id', $issue->id)
            ->whereIn('status', ['blocked', 'expired', 'approved'])
            ->latest('id')
            ->first();
        if (! $approval || $approval->execution?->status !== ai_development_states::BLOCKED) {
            return null;
        }

        if ($approval->status === 'approved') {
            $execution = $approval->execution;
            if (filled($execution->github_task_id) && $execution->github_task_state === 'completed') {
                $this->states->event($execution, 'completed_task_recovery_requested', [
                    'task_id' => $execution->github_task_id,
                    'reason' => 'La ejecucion fue bloqueada despues de completar Copilot.',
                ]);
                monitor_ai_development_agent::dispatch($execution->id);

                return $approval->fresh(['issue', 'project', 'execution']);
            }
            $execution->forceFill([
                'status' => ai_development_states::APPROVED,
                'current_phase' => ai_development_states::APPROVED,
                'error' => null,
                'blocked_reason' => null,
                'github_task_id' => null,
                'github_task_url' => null,
                'github_task_state' => null,
                'github_pull_request_number' => null,
                'context' => Arr::except((array) $execution->context, ['github_task', 'github_pull_request', 'github_pull_request_url', 'qa_merged']),
                'last_activity_at' => now(),
            ])->save();
            $this->states->event($execution, 'approved_execution_resumed', ['approval_id' => $approval->id]);
            run_ai_development_execution::dispatch($execution->id);

            return $approval->fresh(['issue', 'project', 'execution']);
        }

        $token = Str::random(64);
        $approval->forceFill([
            'token_hash' => hash('sha256', $token),
            'status' => 'pending',
            'snapshot' => $this->snapshot($issue, $configuration),
            'story_point_estimate' => null,
            'selected_agent_id' => $configuration->default_agent_id,
            'decided_by_user_id' => null,
            'decided_at' => null,
            'expires_at' => now()->addHours(max(1, (int) config('ai_development.approval_ttl_hours', 48))),
            'decision_note' => null,
            'supervisor_context' => null,
            'blocked_reason' => null,
            'last_notified_at' => null,
        ])->save();

        $execution = $approval->execution;
        $this->states->event($execution, 'approval_retry_requested', ['approval_id' => $approval->id]);
        $execution = $this->states->transition($execution, ai_development_states::AWAITING_APPROVAL, ['retry' => true]);
        $notification = $this->notifications->approval($approval, $token);
        $this->states->event($execution, ($notification['status'] ?? 0) === 1 ? 'approval_sent' : 'approval_notification_failed', [
            'retry' => true,
            'message' => mb_substr((string) ($notification['message'] ?? ''), 0, 500),
        ]);

        return $approval->fresh(['issue', 'project', 'execution']);
    }
    
    public function restartExecution(int $executionId, ?int $userId = null): ai_development_execution
    {
        $execution = ai_development_execution::query()
            ->with(['issue.connection', 'project.jiraProject', 'project.defaultAgent', 'project.githubConnection', 'approval', 'agent'])
            ->findOrFail($executionId);
        $project = $execution->project;
        $issue = $execution->issue;
        $agent = $execution->agent ?: $project?->defaultAgent;

        if (! $project?->enabled || ! $agent?->enabled || ! $project->githubConnection || blank($project->github_owner) || blank($project->github_repository)) {
            throw new RuntimeException('El proyecto, agente o repositorio GitHub no esta habilitado para reiniciar la ejecucion.');
        }
        if (! jira_automation_statuses::isDevelopmentActive($issue->status)) {
            (new jira_client($issue->connection))->transitionIssue($issue->issue_key, jira_automation_statuses::IN_PROGRESS);
        }

        $execution = DB::transaction(function () use ($execution, $issue, $project, $agent, $userId): ai_development_execution {
            $approval = $execution->approval;
            if (! $approval) {
                $approval = ai_development_approval::create([
                    'jira_issue_id' => $issue->id,
                    'jira_automation_project_id' => $project->id,
                    'source_fingerprint' => hash('sha256', 'manual-restart|'.$execution->id.'|'.now()->timestamp),
                    'token_hash' => hash('sha256', Str::random(64)),
                    'status' => 'approved',
                    'snapshot' => $this->snapshot($issue, $project),
                    'selected_agent_id' => $agent->id,
                    'story_point_estimate' => $issue->story_points,
                    'decided_by_user_id' => $userId,
                    'decided_at' => now(),
                    'expires_at' => now()->addHours(48),
                    'decision_note' => 'Reinicio manual de ejecucion.',
                ]);
                $execution->approval_id = $approval->id;
            } else {
                $approval->forceFill([
                    'status' => 'approved',
                    'selected_agent_id' => $agent->id,
                    'decided_by_user_id' => $userId,
                    'decided_at' => now(),
                    'decision_note' => 'Reinicio manual de ejecucion.',
                    'blocked_reason' => null,
                    'expires_at' => now()->addHours(48),
                ])->save();
            }

            ai_development_event::query()
                ->where('execution_id', $execution->id)
                ->orWhere('approval_id', $approval->id)
                ->delete();

            $execution->forceFill([
                'approval_id' => $approval->id,
                'agent_id' => $agent->id,
                'repository' => $this->repository($project),
                'status' => ai_development_states::APPROVED,
                'current_phase' => ai_development_states::APPROVED,
                'feature_branch' => null,
                'base_branch' => $project->base_branch ?: config('ai_development.default_base_branch', 'qa'),
                'attempt' => 0,
                'ci_attempts' => 0,
                'main_ci_attempts' => 0,
                'consecutive_failures' => 0,
                'started_at' => now(),
                'finished_at' => null,
                'last_activity_at' => now(),
                'qa_delivered_at' => null,
                'qa_last_feedback_at' => null,
                'done_detected_at' => null,
                'last_commit_sha' => null,
                'qa_workflow_run_id' => null,
                'main_workflow_run_id' => null,
                'github_task_id' => null,
                'github_task_url' => null,
                'github_task_state' => null,
                'github_pull_request_number' => null,
                'error' => null,
                'blocked_reason' => null,
                'context' => Arr::only((array) $execution->context, ['supervisor_context']),
            ])->save();

            ai_development_event::create([
                'execution_id' => $execution->id,
                'approval_id' => $approval->id,
                'event' => 'manual_execution_restarted',
                'phase' => ai_development_states::APPROVED,
                'actor_type' => 'user',
                'actor_id' => $userId,
                'attempt' => 0,
                'metadata' => ['reason' => 'Reinicio manual solicitado desde GitHub.'],
            ]);

            return $execution->fresh();
        });

        run_ai_development_execution::dispatch($execution->id);

        return $execution->fresh(['issue', 'project', 'agent']);
    }

    public function decideApproval(
        int $approvalId,
        string $token,
        bool $approved,
        ?float $storyPointEstimate,
        ?int $agentId = null,
        ?string $note = null,
        ?int $userId = null,
        ?string $supervisorContext = null,
    ): array {
        $approval = ai_development_approval::query()
            ->with(['issue.connection', 'project.defaultAgent', 'execution'])
            ->whereKey($approvalId)
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
        if ($approval->status !== 'pending' || $approval->expires_at?->isPast()) {
            throw new RuntimeException('La solicitud ya fue utilizada, expiro o no esta disponible.');
        }

        $issue = $approval->issue->fresh(['connection', 'project', 'assignee', 'reporter']);
        $project = $approval->project->fresh(['jiraProject', 'defaultAgent', 'githubConnection']);
        $execution = $approval->execution;
        if (! $execution) {
            throw new RuntimeException('La solicitud no tiene una ejecucion auditable asociada.');
        }
        if (! $this->isCandidate($issue, $project)) {
            $approval->update(['status' => 'blocked', 'decided_at' => now(), 'decided_by_user_id' => $userId, 'decision_note' => 'La historia cambio antes de la decision.']);
            $this->states->block($execution, 'La historia ya no cumple los criterios de candidato al aprobar.');
            $this->notifications->blocked($execution, $execution->blocked_reason);
            throw new RuntimeException('La historia cambio y ya no cumple las condiciones de automatizacion.');
        }

        $supervisorContext = filled($supervisorContext)
            ? mb_substr(trim($supervisorContext), 0, 10000)
            : null;
        $approval->forceFill([
            'story_point_estimate' => $storyPointEstimate,
            'selected_agent_id' => $agentId ?: $project->default_agent_id,
            'decided_at' => now(),
            'decided_by_user_id' => $userId,
            'decision_note' => $note,
            'supervisor_context' => $supervisorContext,
        ])->save();
        if (! $approved) {
            $approval->update(['status' => 'rejected']);
            $execution->update([
                'agent_id' => $agentId ?: $project->default_agent_id,
                'context' => array_merge((array) $execution->context, ['supervisor_context' => $supervisorContext]),
            ]);
            $this->states->transition($execution, ai_development_states::REJECTED, [
                'story_point_estimate' => $storyPointEstimate,
                'supervisor_context_provided' => filled($supervisorContext),
            ]);
            return ['status' => 'rejected', 'approval' => $approval->fresh(), 'execution' => $execution->fresh()];
        }
        if ($storyPointEstimate === null || $storyPointEstimate < 0) {
            throw new RuntimeException('Define un Story Point Estimate valido antes de aprobar.');
        }
        $agent = $agentId ? ai_agent::query()->whereKey($agentId)->where('enabled', true)->first() : $project->defaultAgent;
        if (! $agent) {
            return $this->blockApproval($approval, $execution, 'No hay un agente habilitado para el proyecto.');
        }
        if (! $project->githubConnection || blank($project->github_owner) || blank($project->github_repository)) {
            return $this->blockApproval($approval, $execution, 'El proyecto no tiene un repositorio GitHub configurado.');
        }

        try {
            $field = data_get($issue->connection->settings, 'story_points_field');
            if (blank($field)) {
                throw new RuntimeException('No fue posible resolver el campo Story Point Estimate de Jira.');
            }
            (new jira_client($issue->connection))->updateIssueFields($issue->issue_key, [$field => $storyPointEstimate]);
            $issue->update(['story_points' => $storyPointEstimate]);
            (new jira_client($issue->connection))->transitionIssue($issue->issue_key, jira_automation_statuses::IN_PROGRESS);
        } catch (Throwable $exception) {
            return $this->blockApproval($approval, $execution, jira_client::safeMessage($exception));
        }

        $approval->update(['status' => 'approved']);
        $execution->update([
            'agent_id' => $agent->id,
            'repository' => $this->repository($project),
            'feature_branch' => $this->featureBranch($issue),
            'base_branch' => $project->base_branch ?: config('ai_development.default_base_branch', 'qa'),
            'started_at' => now(),
            'context' => array_merge((array) $execution->context, ['supervisor_context' => $supervisorContext]),
        ]);
        $this->states->transition($execution, ai_development_states::APPROVED, [
            'story_point_estimate' => $storyPointEstimate,
            'supervisor_context_provided' => filled($supervisorContext),
        ]);
        run_ai_development_execution::dispatch($execution->id);

        return ['status' => 'approved', 'approval' => $approval->fresh(), 'execution' => $execution->fresh()];
    }

    public function handleSyncedIssue(jira_issue $issue): void
    {
        $this->detectCandidate($issue);
        $execution = ai_development_execution::query()->where('jira_issue_id', $issue->id)->latest('id')->first();
        if (! $execution) {
            return;
        }
        if (strcasecmp(trim((string) $issue->status), jira_automation_statuses::QA) === 0
            && $execution->status === ai_development_states::WAITING_QUALITY_REVIEW
            && $execution->qa_delivered_at !== null) {
            $feedback = $this->newFeedback($issue, $execution);
            if ($feedback !== []) {
                $execution->update([
                    'qa_last_feedback_at' => now(),
                    'context' => array_merge((array) $execution->context, ['last_feedback' => $feedback]),
                ]);
                $this->states->transition($execution, ai_development_states::QUALITY_FEEDBACK, ['feedback_count' => count($feedback)]);
                run_ai_development_execution::dispatch($execution->id);
            }
        }
        if (strcasecmp(trim((string) $issue->status), jira_automation_statuses::DONE) === 0
            && $execution->status === ai_development_states::WAITING_QUALITY_REVIEW
            && $execution->qa_delivered_at !== null) {
            $execution->update(['done_detected_at' => now()]);
            $this->states->transition($execution, ai_development_states::INTEGRATING_MAIN);
            promote_ai_development_execution::dispatch($execution->id);
        }
    }

    public function isCandidate(jira_issue $issue, jira_automation_project $project): bool
    {
        return $this->candidateReasons($issue, $project, false) === [];
    }

    public function candidateReasons(jira_issue $issue, jira_automation_project $project, bool $includeDuplicateWork = true, bool $requirePendingState = true): array
    {
        $reasons = [];
        if (! $project->enabled) {
            $reasons[] = 'El proyecto de automatizacion esta deshabilitado.';
        }
        if ($requirePendingState && ! jira_automation_statuses::isPending($issue->status)) {
            $reasons[] = 'El estado Jira no es elegible: '.((string) $issue->status ?: 'vacio').'.';
        }
        $matchingType = $project->issueTypes->first(fn ($item): bool => strcasecmp(trim((string) $item->issue_type), trim((string) $issue->issue_type)) === 0);
        if (! $matchingType) {
            $reasons[] = 'El tipo de issue no esta configurado: '.((string) $issue->issue_type ?: 'vacio').'.';
        } elseif (! $matchingType->enabled) {
            $reasons[] = 'El tipo de issue existe pero esta deshabilitado: '.$matchingType->issue_type.'.';
        }
        $assigneeKey = $issue->assignee?->account_id ?: '__unassigned__';
        $matchingAssignee = $project->assignees->first(fn (jira_automation_assignee $item): bool => (string) $item->assignee_key === (string) $assigneeKey);
        if (! $matchingAssignee) {
            $reasons[] = 'El assignee no esta configurado: '.($issue->assignee?->display_name ?: 'Unassigned').' (account_id '.$assigneeKey.').';
        } elseif (! $matchingAssignee->enabled) {
            $reasons[] = 'El assignee existe pero esta deshabilitado: '.($matchingAssignee->display_name ?: $assigneeKey).'.';
        }
        if ($includeDuplicateWork && $this->hasEquivalentPendingWork($issue)) {
            $reasons[] = 'Ya existe una aprobacion pendiente o una ejecucion activa para esta HU.';
        }

        return $reasons;
    }

    public function scanProject(jira_automation_project $project): int
    {
        $count = 0;
        jira_issue::query()->where('jira_project_id', $project->jira_project_id)->chunkById(100, function ($issues) use (&$count): void {
            foreach ($issues as $issue) {
                if ($this->detectCandidate($issue)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    private function blockApproval(ai_development_approval $approval, ai_development_execution $execution, string $reason): array
    {
        $approval->update(['status' => 'blocked', 'blocked_reason' => $reason]);
        $this->states->block($execution, $reason);
        $this->notifications->blocked($execution, $reason);

        return ['status' => 'blocked', 'message' => $reason, 'approval' => $approval->fresh(), 'execution' => $execution->fresh()];
    }

    private function hasEquivalentPendingWork(jira_issue $issue): bool
    {
        return ai_development_approval::query()->where('jira_issue_id', $issue->id)->whereIn('status', ['pending', 'approved'])->exists()
            || ai_development_execution::query()->where('jira_issue_id', $issue->id)->whereNotIn('status', ['rejected', 'completed', 'failed', 'blocked'])->exists();
    }

    private function fingerprint(jira_issue $issue, jira_automation_project $project): string
    {
        return hash('sha256', implode('|', [
            $project->id,
            $issue->id,
            $issue->jira_updated_at?->toIso8601String() ?: $issue->updated_at?->toIso8601String(),
            trim((string) $issue->status),
            trim((string) $issue->issue_type),
            $issue->assignee?->account_id ?: '__unassigned__',
            $issue->story_points,
        ]));
    }

    private function snapshot(jira_issue $issue, jira_automation_project $project): array
    {
        $connection = $issue->connection;
        return [
            'jira_key' => $issue->issue_key,
            'project_key' => $project->jiraProject?->project_key,
            'project_name' => $project->jiraProject?->name,
            'title' => $issue->summary,
            'description' => $issue->description ?: data_get($issue->raw_fields, 'description'),
            'issue_type' => $issue->issue_type,
            'status' => $issue->status,
            'assignee' => $issue->assignee?->display_name ?: 'Unassigned',
            'reporter' => $issue->reporter?->display_name,
            'story_points' => $issue->story_points,
            'jira_url' => $connection?->site_url ? rtrim($connection->site_url, '/').'/browse/'.$issue->issue_key : null,
            'comments' => $issue->comments ?: [],
            'detected_at' => now()->toIso8601String(),
        ];
    }

    private function repository(jira_automation_project $project): ?string
    {
        return filled($project->github_owner) && filled($project->github_repository)
            ? $project->github_owner.'/'.$project->github_repository
            : null;
    }

    private function featureBranch(jira_issue $issue): string
    {
        return trim((string) $issue->issue_key);
    }

    private function newFeedback(jira_issue $issue, ai_development_execution $execution): array
    {
        $deliveredAt = $execution->qa_delivered_at;
        $comments = collect(is_array($issue->comments) ? $issue->comments : [])
            ->filter(function (array $comment) use ($deliveredAt): bool {
                if (! $deliveredAt || blank($comment['created'] ?? null)) {
                    return false;
                }
                try {
                    return \Carbon\Carbon::parse($comment['created'])->greaterThan($deliveredAt);
                } catch (Throwable) {
                    return false;
                }
            })
            ->reject(fn (array $comment): bool => str_contains(strtolower((string) ($comment['content'] ?? '')), 'disponible para revision'))
            ->values()
            ->all();
        if ($comments !== []) {
            return $comments;
        }
        $changed = \App\Models\jira_issue_changelog::query()
            ->where('jira_issue_id', $issue->id)
            ->where('changed_at', '>', $deliveredAt)
            ->exists();

        return $changed ? [['content' => 'Se detectaron cambios Jira posteriores a la entrega en QA.']] : [];
    }
}