<?php

namespace App\Services\Jira;

use App\Models\jira_connection;
use App\Models\jira_issue;
use App\Models\jira_issue_changelog;
use App\Models\jira_issue_worklog;
use App\Models\jira_project;
use App\Models\jira_sync_run;
use App\Models\jira_user;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class jira_sync_service
{
    public function test(jira_connection $connection): array
    {
        try {
            $client = new jira_client($connection);
            $me = $client->testConnection();
            $fields = $client->fields();
            $settings = $this->settings($connection);
            $settings['story_points_field'] = $this->findFieldId($fields, ['story point', 'story points', 'story point estimate']);
            $settings['epic_link_field'] = $this->findFieldId($fields, ['epic link', 'parent link']);
            $connection->update([
                'status' => 'active',
                'settings' => $settings,
                'last_tested_at' => now(),
                'last_error' => null,
            ]);

            return [
                'ok' => true,
                'message' => 'Conexion Jira verificada correctamente.',
                'account' => [
                    'account_id' => $me['accountId'] ?? null,
                    'display_name' => $me['displayName'] ?? null,
                    'email' => $me['emailAddress'] ?? null,
                ],
                'fields' => [
                    'story_points' => $settings['story_points_field'],
                    'epic_link' => $settings['epic_link_field'],
                ],
            ];
        } catch (Throwable $exception) {
            $message = jira_client::safeMessage($exception);
            $connection->update([
                'status' => 'error',
                'last_tested_at' => now(),
                'last_error' => $message,
            ]);

            return ['ok' => false, 'message' => $message];
        }
    }

    public function sync(jira_connection $connection, int $days = 1, bool $full = false, bool $incremental = false): array
    {
        $days = max(1, min($days, 3650));
        $to = now();
        $from = $full
            ? ($this->latestStoryCreatedAt($connection) ?: Carbon::create(1970, 1, 1))
            : ($incremental && $connection->last_sync_at
                ? $connection->last_sync_at->copy()->subMinutes(2)
                : now()->subDays($days));
        $run = jira_sync_run::create([
            'jira_connection_id' => $connection->id,
            'mode' => $full ? 'initial' : ($incremental ? 'incremental' : 'manual'),
            'status' => 'running',
            'cursor_from' => $from,
            'cursor_to' => $to,
            'started_at' => now(),
        ]);

        try {
            $client = new jira_client($connection);
            $fields = $client->fields();
            $settings = $this->settings($connection);
            $settings['story_points_field'] = $settings['story_points_field'] ?? $this->findFieldId($fields, ['story point', 'story points', 'story point estimate']);
            $settings['epic_link_field'] = $settings['epic_link_field'] ?? $this->findFieldId($fields, ['epic link', 'parent link']);
            $connection->update(['settings' => $settings]);

            $projectMap = $this->syncProjects($client, $connection, $run);
            $jql = $full
                ? 'created >= "'.$from->format('Y-m-d H:i').'" ORDER BY created ASC'
                : 'updated >= "'.$from->format('Y-m-d H:i').'" ORDER BY updated ASC';
            $issueFields = $this->issueFields($settings);
            $issueCount = 0;
            $worklogCount = 0;
            $historyCount = 0;
            $userIds = [];
            $startAt = 0;
            $nextPageToken = null;
            $maxIssues = max(1, (int) config('jira.max_issues_per_sync', 5000));
            $pageSize = $full
                ? (int) config('jira.web_batch_size', 10)
                : (int) config('jira.max_page_size', 50);

            do {
                $page = $client->searchIssues($jql, $issueFields, $startAt, $pageSize, $nextPageToken);
                $issues = is_array($page['issues'] ?? null) ? $page['issues'] : [];
                foreach ($issues as $payload) {
                    if ($issueCount >= $maxIssues) {
                        break 2;
                    }
                    $issue = $this->upsertIssue($connection, $payload, $projectMap, $settings, $userIds);
                    $issueCount++;
                    [$worklogs, $history] = $this->syncIssueDetails($client, $connection, $issue, $userIds);
                    $worklogCount += $worklogs;
                    $historyCount += $history;
                }
                $startAt += count($issues);
                $nextPageToken = $page['nextPageToken'] ?? null;
                $last = ($page['isLast'] ?? false) === true || count($issues) === 0;
                if (isset($page['total']) && $startAt >= (int) $page['total']) {
                    $last = true;
                }
                if (($nextPageToken === null || $nextPageToken === '') && ! isset($page['total']) && count($issues) < (int) config('jira.max_page_size', 50)) {
                    $last = true;
                }
            } while (! $last);

            $this->resolveHierarchy($connection);
            $userCount = count($userIds);
            $connection->update([
                'status' => 'active',
                'last_sync_at' => now(),
                'last_error' => null,
            ]);
            $run->update([
                'status' => 'succeeded',
                'records_projects' => $projectMap->count(),
                'records_users' => $userCount,
                'records_issues' => $issueCount,
                'records_worklogs' => $worklogCount,
                'records_history' => $historyCount,
                'finished_at' => now(),
            ]);

            return [
                'ok' => true,
                'message' => "Sincronizacion completada: {$issueCount} issues, {$worklogCount} worklogs.",
                'run' => $run->fresh(),
            ];
        } catch (Throwable $exception) {
            $message = jira_client::safeMessage($exception);
            $connection->update(['status' => 'error', 'last_error' => $message]);
            $run->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
            throw new RuntimeException($message, 0, $exception);
        }
    }

    public function syncBatch(
        jira_connection $connection,
        int $days = 30,
        ?int $maxIssues = null,
        int $startAt = 0,
        ?string $nextPageToken = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $mode = 'updated',
    ): array {
        $days = max(1, min($days, 365));
        $batchSize = max(1, min(
            $maxIssues ?? (int) config('jira.web_batch_size', 5),
            (int) config('jira.max_page_size', 50),
        ));
        $mode = in_array($mode, ['full', 'updated'], true) ? $mode : 'updated';
        $to = $toDate !== null ? Carbon::parse($toDate) : now();
        if ($mode === 'full') {
            $from = $fromDate !== null
                ? Carbon::parse($fromDate)
                : ($this->latestStoryCreatedAt($connection) ?: Carbon::create(1970, 1, 1));
        } else {
            $from = $fromDate !== null ? Carbon::parse($fromDate) : $to->copy()->subDays($days);
        }
        $run = jira_sync_run::create([
            'jira_connection_id' => $connection->id,
            'mode' => 'manual_batch_'.$mode,
            'status' => 'running',
            'cursor_from' => $from,
            'cursor_to' => $to,
            'started_at' => now(),
        ]);

        try {
            $client = new jira_client(
                $connection,
                (float) config('jira.sync_timeout', 8),
                (int) config('jira.sync_retries', 0),
            );
            $fields = $client->fields();
            $settings = $this->settings($connection);
            $settings['story_points_field'] = $settings['story_points_field'] ?? $this->findFieldId($fields, ['story point', 'story points', 'story point estimate']);
            $settings['epic_link_field'] = $settings['epic_link_field'] ?? $this->findFieldId($fields, ['epic link', 'parent link']);
            $connection->update(['settings' => $settings]);

            $projectMap = $this->syncProjects($client, $connection, $run);
            $jql = $mode === 'full'
                ? 'created >= "'.$from->format('Y-m-d H:i').'" ORDER BY created ASC'
                : 'updated >= "'.$from->format('Y-m-d H:i').'" ORDER BY updated ASC';
            $page = $client->searchIssues(
                $jql,
                $this->issueFields($settings),
                max(0, $startAt),
                $batchSize,
                $nextPageToken,
            );
            $issues = is_array($page['issues'] ?? null) ? $page['issues'] : [];
            $issueCount = 0;
            $worklogCount = 0;
            $historyCount = 0;
            $userIds = [];
            foreach ($issues as $payload) {
                $issue = $this->upsertIssue($connection, $payload, $projectMap, $settings, $userIds);
                $issueCount++;
                $issueType = strtolower((string) data_get($payload, 'fields.issuetype.name', ''));
                if ($issueType !== 'epic') {
                    [$worklogs, $history] = $this->syncIssueDetails($client, $connection, $issue, $userIds);
                    $worklogCount += $worklogs;
                    $historyCount += $history;
                }
            }

            $processedUntil = max(0, $startAt) + count($issues);
            $returnedToken = $page['nextPageToken'] ?? null;
            $totalIssues = isset($page['total']) ? (int) $page['total'] : null;
            $hasMore = ($page['isLast'] ?? false) !== true;
            if (isset($page['total'])) {
                $hasMore = $processedUntil < (int) $page['total'];
            } elseif ($returnedToken !== null && $returnedToken !== '') {
                $hasMore = true;
            } elseif (count($issues) < $batchSize) {
                $hasMore = false;
            }

            if (! $hasMore) {
                $this->resolveHierarchy($connection);
                $connection->update([
                    'status' => 'active',
                    'last_sync_at' => now(),
                    'last_error' => null,
                ]);
            }

            $run->update([
                'status' => 'succeeded',
                'records_projects' => $projectMap->count(),
                'records_users' => count($userIds),
                'records_issues' => $issueCount,
                'records_worklogs' => $worklogCount,
                'records_history' => $historyCount,
                'finished_at' => now(),
            ]);

            return [
                'ok' => true,
                'complete' => ! $hasMore,
                'has_more' => $hasMore,
                'next_start_at' => $returnedToken === null || $returnedToken === '' ? $processedUntil : 0,
                'next_page_token' => $returnedToken,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'records_issues' => $issueCount,
                'records_worklogs' => $worklogCount,
                'total_issues' => $totalIssues,
                'batch_size' => $batchSize,
                'message' => $hasMore
                    ? "Lote sincronizado: {$issueCount} issues. Continuando..."
                    : "Sincronizacion completada: {$issueCount} issues, {$worklogCount} worklogs.",
            ];
        } catch (Throwable $exception) {
            $message = jira_client::safeMessage($exception);
            $connection->update(['status' => 'error', 'last_error' => $message]);
            $run->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
            throw new RuntimeException($message, 0, $exception);
        }
    }

    private function latestStoryCreatedAt(jira_connection $connection): ?Carbon
    {
        $latest = jira_issue::query()
            ->where('jira_connection_id', $connection->id)
            ->whereRaw('LOWER(issue_type) = ?', ['story'])
            ->max('jira_created_at');

        if ($latest === null) {
            $latest = jira_issue::query()
                ->where('jira_connection_id', $connection->id)
                ->max('jira_created_at');
        }

        return $latest !== null ? Carbon::parse($latest) : null;
    }

    private function syncProjects(jira_client $client, jira_connection $connection, jira_sync_run $run)
    {
        $startAt = 0;
        $projects = collect();
        do {
            $page = $client->projects($startAt, (int) config('jira.max_page_size', 50));
            $items = is_array($page['values'] ?? null) ? $page['values'] : [];
            foreach ($items as $payload) {
                $project = jira_project::updateOrCreate(
                    ['jira_connection_id' => $connection->id, 'external_id' => (string) ($payload['id'] ?? $payload['key'])],
                    [
                        'project_key' => (string) ($payload['key'] ?? ''),
                        'name' => Str::limit(trim((string) ($payload['name'] ?? $payload['key'] ?? 'Proyecto Jira')), 255, ''),
                        'project_type' => $payload['projectTypeKey'] ?? null,
                        'category' => data_get($payload, 'projectCategory.name'),
                        'status' => ($payload['archived'] ?? false) ? 'archived' : 'active',
                        'lead_account_id' => data_get($payload, 'lead.accountId'),
                        'metadata' => Arr::only($payload, ['id', 'key', 'name', 'style', 'simplified', 'projectTypeKey']),
                        'jira_updated_at' => $this->date($payload['updated'] ?? null),
                        'last_seen_at' => now(),
                    ],
                );
                $projects->put($project->project_key, $project);
            }
            $startAt += count($items);
            $last = ($page['isLast'] ?? false) === true || count($items) === 0;
            if (isset($page['total']) && $startAt >= (int) $page['total']) {
                $last = true;
            }
        } while (! $last);

        return $projects;
    }

    private function upsertIssue(jira_connection $connection, array $payload, $projectMap, array $settings, array &$userIds): jira_issue
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $projectKey = (string) data_get($fields, 'project.key', '');
        $project = $projectMap->get($projectKey) ?: jira_project::where('jira_connection_id', $connection->id)->where('project_key', $projectKey)->first();
        if ($project === null) {
            throw new RuntimeException('Jira devolvio un issue sin proyecto local: '.$projectKey);
        }

        $assignee = $this->upsertUser($connection, $fields['assignee'] ?? null, $userIds);
        $reporter = $this->upsertUser($connection, $fields['reporter'] ?? null, $userIds);
        $parentKey = data_get($fields, 'parent.key');
        $issueTypeName = data_get($fields, 'issuetype.name');
        $epicKey = $this->fieldValue($fields, $settings['epic_link_field'] ?? null);
        if ($epicKey === null && strtolower((string) $issueTypeName) === 'epic') {
            $epicKey = null;
        }
        if ($epicKey === null && data_get($fields, 'parent.fields.issuetype.name') === 'Epic') {
            $epicKey = $parentKey;
        }

        $rawFields = [
            'parent_key' => $parentKey,
            'epic_key' => is_scalar($epicKey) ? (string) $epicKey : null,
            'story_points_field' => $settings['story_points_field'] ?? null,
            'epic_link_field' => $settings['epic_link_field'] ?? null,
        ];
        $issue = jira_issue::updateOrCreate(
            ['jira_connection_id' => $connection->id, 'external_id' => (string) ($payload['id'] ?? $payload['key'])],
            [
                'jira_project_id' => $project->id,
                'issue_key' => (string) ($payload['key'] ?? ''),
                'issue_type' => $issueTypeName,
                'summary' => Str::limit(trim((string) ($fields['summary'] ?? 'Sin titulo')), 500, ''),
                'status' => data_get($fields, 'status.name'),
                'status_category' => data_get($fields, 'status.statusCategory.name'),
                'priority' => data_get($fields, 'priority.name'),
                'assignee_jira_user_id' => $assignee?->id,
                'reporter_jira_user_id' => $reporter?->id,
                'story_points' => $this->numeric($this->fieldValue($fields, $settings['story_points_field'] ?? null)),
                'original_estimate_seconds' => $fields['timeoriginalestimate'] ?? null,
                'time_spent_seconds' => $fields['timespent'] ?? null,
                'jira_created_at' => $this->date($fields['created'] ?? null),
                'jira_updated_at' => $this->date($fields['updated'] ?? null),
                'jira_resolved_at' => $this->date($fields['resolutiondate'] ?? null),
                'due_date' => $this->dateOnly($fields['duedate'] ?? null),
                'labels' => array_values(array_filter((array) ($fields['labels'] ?? []))),
                'components' => collect((array) ($fields['components'] ?? []))->pluck('name')->filter()->values()->all(),
                'sprints' => $this->sprints($fields['customfield_10020'] ?? []),
                'raw_fields' => $rawFields,
            ],
        );

        return $issue;
    }

    private function syncIssueDetails(jira_client $client, jira_connection $connection, jira_issue $issue, array &$userIds): array
    {
        $worklogs = 0;
        $history = 0;
        try {
            $startAt = 0;
            do {
                $page = $client->worklogs($issue->issue_key, $startAt, 100);
                $items = is_array($page['worklogs'] ?? null) ? $page['worklogs'] : [];
                foreach ($items as $payload) {
                    $user = $this->upsertUser($connection, $payload['author'] ?? null, $userIds);
                    jira_issue_worklog::updateOrCreate(
                        ['jira_connection_id' => $connection->id, 'external_id' => (string) ($payload['id'] ?? '')],
                        [
                            'jira_issue_id' => $issue->id,
                            'jira_user_id' => $user?->id,
                            'started_at' => $this->date($payload['started'] ?? null),
                            'updated_at_jira' => $this->date($payload['updated'] ?? null),
                            'time_spent_seconds' => (int) ($payload['timeSpentSeconds'] ?? 0),
                            'is_deleted' => false,
                            'metadata' => Arr::only($payload, ['id', 'timeSpent', 'timeSpentSeconds', 'started', 'updated']),
                        ],
                    );
                    $worklogs++;
                }
                $startAt += count($items);
                $last = count($items) === 0 || $startAt >= (int) ($page['total'] ?? $startAt);
            } while (! $last);
        } catch (Throwable $exception) {
            logger()->warning('No fue posible sincronizar worklogs Jira.', ['issue' => $issue->issue_key, 'message' => jira_client::safeMessage($exception)]);
        }

        try {
            $startAt = 0;
            do {
                $page = $client->changelog($issue->issue_key, $startAt, 100);
                $histories = is_array($page['values'] ?? null) ? $page['values'] : (is_array($page['histories'] ?? null) ? $page['histories'] : []);
                foreach ($histories as $entry) {
                    $author = $this->upsertUser($connection, $entry['author'] ?? null, $userIds);
                    foreach ((array) ($entry['items'] ?? []) as $item) {
                        $field = (string) ($item['field'] ?? '');
                        if (! in_array(strtolower($field), ['status', 'assignee', 'story points', 'story point estimate', 'epic link', 'parent'], true)) {
                            continue;
                        }
                        jira_issue_changelog::updateOrCreate(
                            ['jira_connection_id' => $connection->id, 'external_history_id' => (string) ($entry['id'] ?? ''), 'field' => $field],
                            [
                                'jira_issue_id' => $issue->id,
                                'author_jira_user_id' => $author?->id,
                                'changed_at' => $this->date($entry['created'] ?? null),
                                'from_value' => isset($item['fromString']) ? (string) $item['fromString'] : (isset($item['from']) ? (string) $item['from'] : null),
                                'to_value' => isset($item['toString']) ? (string) $item['toString'] : (isset($item['to']) ? (string) $item['to'] : null),
                                'metadata' => Arr::only($item, ['fieldtype', 'fieldId', 'from', 'fromString', 'to', 'toString']),
                            ],
                        );
                        $history++;
                    }
                }
                $startAt += count($histories);
                $last = count($histories) === 0 || $startAt >= (int) ($page['total'] ?? $startAt);
            } while (! $last);
        } catch (Throwable $exception) {
            logger()->warning('No fue posible sincronizar historial Jira.', ['issue' => $issue->issue_key, 'message' => jira_client::safeMessage($exception)]);
        }

        return [$worklogs, $history];
    }

    private function resolveHierarchy(jira_connection $connection): void
    {
        jira_issue::where('jira_connection_id', $connection->id)->chunkById(200, function ($issues): void {
            foreach ($issues as $issue) {
                $raw = (array) $issue->raw_fields;
                $parentId = ! empty($raw['parent_key']) ? jira_issue::where('jira_connection_id', $issue->jira_connection_id)->where('issue_key', $raw['parent_key'])->value('id') : null;
                $epicId = ! empty($raw['epic_key']) ? jira_issue::where('jira_connection_id', $issue->jira_connection_id)->where('issue_key', $raw['epic_key'])->value('id') : null;
                if ($issue->parent_jira_issue_id !== $parentId || $issue->epic_jira_issue_id !== $epicId) {
                    $issue->update(['parent_jira_issue_id' => $parentId, 'epic_jira_issue_id' => $epicId]);
                }
            }
        });
    }

    private function upsertUser(jira_connection $connection, ?array $payload, array &$userIds): ?jira_user
    {
        if (! is_array($payload) || blank($payload['accountId'] ?? null)) {
            return null;
        }
        $user = jira_user::updateOrCreate(
            ['jira_connection_id' => $connection->id, 'account_id' => (string) $payload['accountId']],
            [
                'display_name' => Str::limit(trim((string) ($payload['displayName'] ?? $payload['accountId'])), 255, ''),
                'email' => $payload['emailAddress'] ?? null,
                'active' => (bool) ($payload['active'] ?? true),
                'avatar_url' => data_get($payload, 'avatarUrls.48x48'),
                'metadata' => Arr::only($payload, ['accountId', 'displayName', 'active', 'accountType']),
                'last_seen_at' => now(),
            ],
        );
        $userIds[$user->id] = true;

        return $user;
    }

    private function issueFields(array $settings): array
    {
        return array_values(array_filter(array_unique([
            'summary', 'project', 'issuetype', 'status', 'priority', 'assignee', 'reporter', 'parent',
            'labels', 'components', 'created', 'updated', 'resolutiondate', 'duedate',
            'timeoriginalestimate', 'timespent', 'customfield_10020',
            $settings['story_points_field'] ?? null,
            $settings['epic_link_field'] ?? null,
        ])));
    }

    private function settings(jira_connection $connection): array
    {
        return array_merge([
            'timezone' => config('jira.default_timezone', 'America/Bogota'),
        ], (array) $connection->settings);
    }

    private function findFieldId(array $fields, array $names): ?string
    {
        foreach ($fields as $field) {
            $name = strtolower((string) ($field['name'] ?? ''));
            foreach ($names as $candidate) {
                if (str_contains($name, strtolower($candidate))) {
                    return (string) ($field['id'] ?? '');
                }
            }
        }

        return null;
    }

    private function fieldValue(array $fields, ?string $field): mixed
    {
        return $field !== null && array_key_exists($field, $fields) ? $fields[$field] : null;
    }

    private function sprints(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        return collect($value)->map(function ($sprint): string {
            if (is_array($sprint)) {
                return (string) ($sprint['name'] ?? $sprint['id'] ?? '');
            }
            return (string) $sprint;
        })->filter()->values()->all();
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function dateOnly(mixed $value): ?Carbon
    {
        return $this->date($value)?->startOfDay();
    }
}
