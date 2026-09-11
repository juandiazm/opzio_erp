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
        $from = Carbon::parse($filters['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay();
        $statuses = collect($filters['statuses'] ?? [])
            ->filter(fn ($status): bool => filled($status))
            ->map(fn ($status): string => trim((string) $status))
            ->filter()
            ->unique()
            ->values();
        $projectIds = $this->filterIds($filters['project_ids'] ?? $filters['project_id'] ?? null);
        $epicIds = $this->filterIds($filters['epic_ids'] ?? $filters['epic_id'] ?? null);
        $userIds = $this->filterIds($filters['user_ids'] ?? $filters['user_id'] ?? null);
        $issueQuery = jira_issue::query()
            ->with(['project', 'assignee.mapping.user', 'assignee.mapping.employee', 'epic'])
            ->where(function ($query): void {
                $query->whereRaw('LOWER(TRIM(issue_type)) IN (?, ?, ?, ?)', ['story', 'user story', 'historia', 'historia de usuario']);
            })
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('jira_created_at', [$from, $to])
                    ->orWhereBetween('jira_updated_at', [$from, $to])
                    ->orWhereBetween('jira_resolved_at', [$from, $to]);
            })
            ->when($projectIds->isNotEmpty(), fn ($query) => $query->whereIn('jira_project_id', $projectIds->all()))
            ->when($epicIds->isNotEmpty(), fn ($query) => $query->whereIn('epic_jira_issue_id', $epicIds->all()))
            ->when($userIds->isNotEmpty(), fn ($query) => $query->whereIn('assignee_jira_user_id', $userIds->all()))
            ->when($statuses->isNotEmpty(), fn ($query) => $query->whereIn('status', $statuses->all()));
        $issues = $issueQuery->orderByRaw('COALESCE(jira_resolved_at, jira_updated_at, jira_created_at)')->get();
        $worklogs = jira_issue_worklog::query()
            ->with(['user.mapping.user', 'user.mapping.employee', 'issue.project', 'issue.epic'])
            ->where('is_deleted', false)
            ->whereBetween('started_at', [$from, $to])
            ->when($projectIds->isNotEmpty(), fn ($query) => $query->whereHas('issue', fn ($issueQuery) => $issueQuery->whereIn('jira_project_id', $projectIds->all())))
            ->when($epicIds->isNotEmpty(), fn ($query) => $query->whereHas('issue', fn ($issueQuery) => $issueQuery->whereIn('epic_jira_issue_id', $epicIds->all())))
            ->when($userIds->isNotEmpty(), fn ($query) => $query->whereIn('jira_user_id', $userIds->all()))
            ->get();

        $projects = [];
        $users = [];
        $epics = [];
        $daily = [];
        foreach ($issues as $issue) {
            $points = (float) ($issue->story_points ?? 0);
            $estimatedHours = $issue->estimated_hours !== null
                ? (float) $issue->estimated_hours
                : $points * (float) ($issue->project?->story_point_hours_multiplier ?? 1);
            $projectKey = (string) ($issue->project?->project_key ?: 'sin_proyecto');
            $projectName = (string) ($issue->project?->name ?: 'Proyecto sin nombre');
            $projects[$projectKey] ??= ['label' => $projectName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0, 'estimated_hours' => 0.0];
            $projects[$projectKey]['story_points'] += $points;
            $projects[$projectKey]['issues']++;
            $projects[$projectKey]['estimated_hours'] += $estimatedHours;
            $userKey = (string) ($issue->assignee?->account_id ?: 'sin_responsable');
            $userName = (string) ($issue->assignee?->display_name ?: 'Sin responsable');
            $userAvatar = $this->assigneeAvatarUrl($issue->assignee);
            $users[$userKey] ??= ['label' => $userName, 'avatar' => $userAvatar, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0, 'estimated_hours' => 0.0, 'project_story_points' => []];
            if (blank($users[$userKey]['avatar']) && filled($userAvatar)) {
                $users[$userKey]['avatar'] = $userAvatar;
            }
            $users[$userKey]['story_points'] += $points;
            $users[$userKey]['estimated_hours'] += $estimatedHours;
            $users[$userKey]['issues']++;
            $users[$userKey]['project_story_points'][$projectKey] ??= ['label' => $projectName, 'story_points' => 0.0];
            $users[$userKey]['project_story_points'][$projectKey]['story_points'] += $points;
            $epicKey = (string) ($issue->epic?->issue_key ?: 'sin_epica');
            $epicName = (string) ($issue->epic?->summary ?: 'Sin epica');
            $epics[$epicKey] ??= ['label' => $epicName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0, 'estimated_hours' => 0.0];
            $epics[$epicKey]['story_points'] += $points;
            $epics[$epicKey]['issues']++;
            $epics[$epicKey]['estimated_hours'] += $estimatedHours;
            $date = $issue->jira_resolved_at?->toDateString() ?: $issue->jira_updated_at?->toDateString() ?: $issue->jira_created_at?->toDateString() ?: 'sin_fecha';
            $daily[$date] ??= ['date' => $date, 'story_points' => 0.0, 'issues' => 0];
            $daily[$date]['story_points'] += $points;
            $daily[$date]['issues']++;
        }

        foreach ($worklogs as $worklog) {
            $hours = ((int) $worklog->time_spent_seconds) / 3600;
            $userKey = (string) ($worklog->user?->account_id ?: 'sin_autor');
            $userName = (string) ($worklog->user?->display_name ?: 'Sin autor');
            $userAvatar = $this->assigneeAvatarUrl($worklog->user);
            $users[$userKey] ??= ['label' => $userName, 'avatar' => $userAvatar, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0, 'estimated_hours' => 0.0, 'project_story_points' => []];
            if (blank($users[$userKey]['avatar']) && filled($userAvatar)) {
                $users[$userKey]['avatar'] = $userAvatar;
            }
            $users[$userKey]['hours'] += $hours;
            $issueProjectKey = (string) ($worklog->issue?->project?->project_key ?: 'sin_proyecto');
            $issueProjectName = (string) ($worklog->issue?->project?->name ?: 'Proyecto sin nombre');
            $projects[$issueProjectKey] ??= ['label' => $issueProjectName, 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0, 'estimated_hours' => 0.0];
            $projects[$issueProjectKey]['hours'] += $hours;
            $issueEpicKey = (string) ($worklog->issue?->epic?->issue_key ?: 'sin_epica');
            $epics[$issueEpicKey] ??= ['label' => (string) ($worklog->issue?->epic?->summary ?: 'Sin epica'), 'story_points' => 0.0, 'issues' => 0, 'hours' => 0.0, 'estimated_hours' => 0.0];
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
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'project_ids' => $projectIds->all(), 'epic_ids' => $epicIds->all(), 'user_ids' => $userIds->all(), 'statuses' => $statuses->all()],
            'summary' => [
                'story_points' => round($issues->sum(fn (jira_issue $issue): float => (float) ($issue->story_points ?? 0)), 2),
                'story_issues' => $issues->count(),
                'completed_issues' => $issues->filter(fn (jira_issue $issue): bool => $issue->jira_resolved_at !== null)->count(),
                'worklog_hours' => round($worklogs->sum(fn (jira_issue_worklog $worklog): float => ((int) $worklog->time_spent_seconds) / 3600), 2),
                'estimated_hours' => round($issues->sum(function (jira_issue $issue): float {
                    return $issue->estimated_hours !== null
                        ? (float) $issue->estimated_hours
                        : (float) ($issue->story_points ?? 0) * (float) ($issue->project?->story_point_hours_multiplier ?? 1);
                }), 2),
                'active_projects' => collect($projects)->filter(fn (array $item): bool => $item['issues'] > 0)->count(),
                'active_users' => collect($users)->filter(fn (array $item): bool => $item['issues'] > 0 || $item['hours'] > 0)->count(),
                'last_sync' => $lastSync?->toIso8601String(),
            ],
            'projects' => $this->sortAggregates($projects),
            'users' => $this->sortUserAggregates($users),
            'epics' => $this->sortAggregates($epics),
            'daily' => collect($daily)->sortBy('date')->values()->all(),
            'issues' => $issues->map(fn (jira_issue $issue): array => [
                'id' => $issue->id,
                'key' => $issue->issue_key,
                'summary' => $issue->summary,
                'project' => $issue->project?->project_key ?: 'Sin proyecto',
                'epic' => $issue->epic?->summary ?: 'Sin epica',
                'assignee' => $issue->assignee?->display_name ?: 'Sin responsable',
                'assignee_avatar' => $this->assigneeAvatarUrl($issue->assignee),
                'status' => $issue->status ?: 'Sin estado',
                'issue_type' => $issue->issue_type ?: 'Sin tipo',
                'story_points' => (float) ($issue->story_points ?? 0),
                'estimated_hours' => $issue->estimated_hours !== null
                    ? (float) $issue->estimated_hours
                    : (float) ($issue->story_points ?? 0) * (float) ($issue->project?->story_point_hours_multiplier ?? 1),
                'estimated_hours_manual' => (bool) $issue->estimated_hours_manual,
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
        return collect($items)->sortByDesc(fn (array $item): float => (float) $item['story_points'] + (float) ($item['estimated_hours'] ?? 0))->values()->all();
    }

    private function sortUserAggregates(array $users): array
    {
        return collect($users)
            ->map(function (array $user): array {
                $topProject = collect($user['project_story_points'] ?? [])->sortByDesc('story_points')->first();
                unset($user['project_story_points']);
                $user['top_project'] = $topProject ? [
                    'label' => $topProject['label'],
                    'story_points' => round((float) $topProject['story_points'], 2),
                ] : null;
                return $user;
            })
            ->sortByDesc(fn (array $item): float => (float) $item['story_points'] + (float) ($item['estimated_hours'] ?? 0))
            ->values()
            ->all();
    }

    private function filterIds(mixed $value): Collection
    {
        return collect(is_array($value) ? $value : (filled($value) ? [$value] : []))
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }

    private function assigneeAvatarUrl(?jira_user $jiraUser): ?string
    {
        $mapping = $jiraUser?->mapping;
        $employeePhoto = $mapping?->employee?->photo;
        if (filled($employeePhoto)) {
            return url('storage/images/erp/employees/'.ltrim((string) $employeePhoto, '/'));
        }

        $userPhoto = $mapping?->user?->photo;
        if (filled($userPhoto)) {
            return url('storage/images/erp/users/'.ltrim((string) $userPhoto, '/'));
        }

        return filled($jiraUser?->avatar_url) ? $jiraUser->avatar_url : null;
    }
}
