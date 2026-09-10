<?php

namespace App\Services\Jira;

use App\Models\jira_issue;
use App\Models\jira_issue_worklog;
use App\Models\jira_project;
use App\Models\jira_user;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class jira_metrics_service
{
    public function dashboard(array $filters = []): array
    {
        $from = Carbon::parse($filters['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay();
        $issueQuery = jira_issue::query()
            ->with(['project', 'assignee', 'epic'])
            ->whereNotNull('jira_resolved_at')
            ->whereBetween('jira_resolved_at', [$from, $to])
            ->where(function ($query): void {
                $query->whereNull('issue_type')
                    ->orWhereRaw('LOWER(issue_type) NOT LIKE ?', ['%epic%']);
            })
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->where('jira_project_id', (int) $filters['project_id']))
            ->when(filled($filters['epic_id'] ?? null), fn ($query) => $query->where('epic_jira_issue_id', (int) $filters['epic_id']))
            ->when(filled($filters['user_id'] ?? null), fn ($query) => $query->where('assignee_jira_user_id', (int) $filters['user_id']));
        $issues = $issueQuery->orderBy('jira_resolved_at')->get();
        $worklogs = jira_issue_worklog::query()
            ->with(['user', 'issue.project', 'issue.epic'])
            ->where('is_deleted', false)
            ->whereBetween('started_at', [$from, $to])
            ->when(filled($filters['project_id'] ?? null), fn ($query) => $query->whereHas('issue', fn ($issueQuery) => $issueQuery->where('jira_project_id', (int) $filters['project_id'])))
            ->when(filled($filters['epic_id'] ?? null), fn ($query) => $query->whereHas('issue', fn ($issueQuery) => $issueQuery->where('epic_jira_issue_id', (int) $filters['epic_id'])))
            ->when(filled($filters['user_id'] ?? null), fn ($query) => $query->where('jira_user_id', (int) $filters['user_id']))
            ->get();

        $projects = [];
        $users = [];
        $epics = [];
        $daily = [];
        foreach ($issues as $issue) {
            $points = (float) ($issue->story_points ?? 0);
            $projectKey = (string) ($issue->project?->project_key ?: 'sin_proyecto');
            $projectName = (string) ($issue->project?->name ?: 'Proyecto sin nombre');
            $projects[$projectKey] ??= ['label' => $projectName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0];
            $projects[$projectKey]['story_points'] += $points;
            $projects[$projectKey]['issues']++;
            $userKey = (string) ($issue->assignee?->account_id ?: 'sin_responsable');
            $userName = (string) ($issue->assignee?->display_name ?: 'Sin responsable');
            $users[$userKey] ??= ['label' => $userName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0];
            $users[$userKey]['story_points'] += $points;
            $users[$userKey]['issues']++;
            $epicKey = (string) ($issue->epic?->issue_key ?: 'sin_epica');
            $epicName = (string) ($issue->epic?->summary ?: 'Sin epica');
            $epics[$epicKey] ??= ['label' => $epicName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0];
            $epics[$epicKey]['story_points'] += $points;
            $epics[$epicKey]['issues']++;
            $date = $issue->jira_resolved_at?->toDateString() ?: 'sin_fecha';
            $daily[$date] ??= ['date' => $date, 'story_points' => 0.0, 'issues' => 0];
            $daily[$date]['story_points'] += $points;
            $daily[$date]['issues']++;
        }

        foreach ($worklogs as $worklog) {
            $hours = ((int) $worklog->time_spent_seconds) / 3600;
            $userKey = (string) ($worklog->user?->account_id ?: 'sin_autor');
            $userName = (string) ($worklog->user?->display_name ?: 'Sin autor');
            $users[$userKey] ??= ['label' => $userName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0];
            $users[$userKey]['hours'] += $hours;
            $issueProjectKey = (string) ($worklog->issue?->project?->project_key ?: 'sin_proyecto');
            $projects[$issueProjectKey] ??= ['label' => (string) ($worklog->issue?->project?->name ?: 'Proyecto sin nombre'), 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0];
            $projects[$issueProjectKey]['hours'] += $hours;
            $issueEpicKey = (string) ($worklog->issue?->epic?->issue_key ?: 'sin_epica');
            $epics[$issueEpicKey] ??= ['label' => (string) ($worklog->issue?->epic?->summary ?: 'Sin epica'), 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0];
            $epics[$issueEpicKey]['hours'] += $hours;
        }

        $lastSync = jira_project::query()
            ->with('connection:id,last_sync_at')
            ->get()
            ->pluck('connection.last_sync_at')
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'project_id' => $filters['project_id'] ?? null, 'epic_id' => $filters['epic_id'] ?? null, 'user_id' => $filters['user_id'] ?? null],
            'summary' => [
                'story_points' => round($issues->sum(fn (jira_issue $issue): float => (float) ($issue->story_points ?? 0)), 2),
                'completed_issues' => $issues->count(),
                'worklog_hours' => round($worklogs->sum(fn (jira_issue_worklog $worklog): float => ((int) $worklog->time_spent_seconds) / 3600), 2),
                'active_projects' => collect($projects)->filter(fn (array $item): bool => $item['issues'] > 0)->count(),
                'active_users' => collect($users)->filter(fn (array $item): bool => $item['issues'] > 0 || $item['hours'] > 0)->count(),
                'last_sync' => $lastSync?->toIso8601String(),
            ],
            'projects' => $this->sortAggregates($projects),
            'users' => $this->sortAggregates($users),
            'epics' => $this->sortAggregates($epics),
            'daily' => collect($daily)->sortBy('date')->values()->all(),
            'issues' => $issues->map(fn (jira_issue $issue): array => [
                'key' => $issue->issue_key,
                'summary' => $issue->summary,
                'project' => $issue->project?->project_key ?: 'Sin proyecto',
                'epic' => $issue->epic?->summary ?: 'Sin epica',
                'assignee' => $issue->assignee?->display_name ?: 'Sin responsable',
                'status' => $issue->status ?: 'Sin estado',
                'issue_type' => $issue->issue_type ?: 'Sin tipo',
                'story_points' => (float) ($issue->story_points ?? 0),
                'resolved_at' => $issue->jira_resolved_at?->toDateString(),
            ])->values()->all(),
        ];
    }

    public function catalog(): array
    {
        return [
            'projects' => jira_project::query()->orderBy('name')->get(['id', 'project_key', 'name'])->values()->all(),
            'epics' => jira_issue::query()->where('issue_type', 'like', '%Epic%')->orderBy('summary')->get(['id', 'issue_key', 'summary', 'jira_project_id'])->values()->all(),
            'users' => jira_user::query()->with('mapping')->orderBy('display_name')->get(['id', 'account_id', 'display_name'])->values()->all(),
        ];
    }

    private function sortAggregates(array $items): array
    {
        return collect($items)->sortByDesc(fn (array $item): float => (float) $item['story_points'] + (float) $item['hours'])->values()->all();
    }
}
