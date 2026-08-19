<?php

namespace App\Http\Controllers;

use App\Domain\Servers\Models\servers_host;
use App\Domain\Servers\Models\servers_project;
use App\Exportable\servers_projects;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\traits\servers_trait;

class servers_dashboard_controller extends Controller
{
    use servers_trait;

    public function page()
    {
        return view('erp.servers');
    }

    public function get_project_config(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer|min:1',
        ]);

        $response = $this->Servers_GetProjectConfig($request->project_id);

        return $response['status'] == 1
            ? $response
            : \Response::json($response, 400);
    }

    public function get_project_recipients(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|min:1',
        ]);

        $response = $this->Servers_GetProjectRecipients($request->client_id);

        return $response['status'] == 1
            ? $response
            : \Response::json($response, 400);
    }

    public function update_project_config(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|min:1',
            'client_id' => 'nullable|integer|min:1',
            'notifications_enabled' => 'required|boolean',
            'recipient_keys' => 'nullable|array|max:500',
            'recipient_keys.*' => 'string|max:255',
        ]);

        $response = $this->Servers_UpdateProjectConfig(
            $validated['project_id'],
            $validated['client_id'] ?? null,
            $validated['notifications_enabled'],
            $validated['recipient_keys'] ?? []
        );

        return $response['status'] == 1
            ? $response
            : \Response::json($response, 400);
    }

    public function add_project_notification(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|min:1',
            'channel' => 'required|in:email,phone',
            'value' => 'required|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
        ]);

        $response = $this->Servers_AddProjectNotification(
            $validated['project_id'],
            $validated['channel'],
            $validated['value'],
            $validated['recipient_name'] ?? null
        );

        return $response['status'] == 1
            ? $response
            : \Response::json($response, 400);
    }

    public function update_project_notification(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|min:1',
            'notification_id' => 'required|integer|min:1',
            'channel' => 'required|in:email,phone',
            'value' => 'required|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
        ]);

        $response = $this->Servers_UpdateProjectNotification(
            $validated['project_id'],
            $validated['notification_id'],
            $validated['channel'],
            $validated['value'],
            $validated['recipient_name'] ?? null
        );

        return $response['status'] == 1
            ? $response
            : \Response::json($response, 400);
    }

    public function delete_project_notification(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|min:1',
            'notification_id' => 'required|integer|min:1',
        ]);

        $response = $this->Servers_DeleteProjectNotification(
            $validated['project_id'],
            $validated['notification_id']
        );

        return $response['status'] == 1
            ? $response
            : \Response::json($response, 400);
    }

    public function get_page(Request $request)
    {
        $filters = $this->dashboardFilters($request);
        $rows = $this->projectRows($filters);
        $total = $rows->count();
        $totalPages = $total > 0 ? (int) ceil($total / $filters['per_page']) : 0;
        $page = $totalPages > 0 ? min($filters['page'], $totalPages) : 1;

        return response()->json([
            'status' => 1,
            'minutes' => $filters['minutes'],
            'generated_at' => now()->toIso8601String(),
            'sort_by' => $filters['sort_by'],
            'sort_direction' => $filters['sort_direction'],
            'pagination' => [
                'page' => $page,
                'per_page' => $filters['per_page'],
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'totals' => $this->projectTotals($rows),
            'filters' => [
                'hosts' => servers_host::where('enabled', true)
                    ->orderBy('name')
                    ->get(['key', 'name'])
                    ->values(),
                'environments' => servers_project::where('enabled', true)
                    ->distinct()
                    ->orderBy('environment')
                    ->pluck('environment')
                    ->values(),
            ],
            'data' => $rows->forPage($page, $filters['per_page'])->values(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->dashboardFilters($request);
        $rows = $this->projectRows($filters);

        return Excel::download(
            new servers_projects($rows),
            'servidores-' . now()->format('Y-m-d_H-i') . '.xlsx'
        );
    }

    public function summary(Request $request)
    {
        $minutes = (int) $request->input('minutes', 1440);
        $minutes = max(15, min($minutes, 43200));
        $from = now()->subMinutes($minutes);
        $staleAt = now()->subMinutes(2);
        $hosts = servers_host::with(['projects', 'agents'])
            ->where('enabled', true)
            ->orderBy('key')
            ->get();

        $payload = $hosts->map(function ($host) use ($from, $staleAt, $minutes) {
            $agent = $host->agents
                ->sortByDesc(function ($item) {
                    return $item->last_seen_at ? $item->last_seen_at->timestamp : 0;
                })
                ->first();
            $hostSample = DB::table('servers_host_samples')
                ->where('host_id', $host->id)
                ->orderByDesc('sampled_at')
                ->first();

            return [
                'key' => $host->key,
                'name' => $host->name,
                'hostname' => $host->hostname,
                'environment' => $host->environment,
                'agent' => [
                    'id' => $agent ? $agent->agent_id : null,
                    'version' => $agent ? $agent->version : null,
                    'last_seen_at' => $agent ? $agent->last_seen_at : null,
                    'status' => $agent && $agent->last_seen_at && $agent->last_seen_at->greaterThan($staleAt)
                        ? 'healthy'
                        : 'stale',
                    'spool_bytes' => $agent ? (int) $agent->spool_bytes : 0,
                    'spool_batches' => $agent ? (int) $agent->spool_batches : 0,
                ],
                'sample' => $hostSample,
                'projects' => $host->projects
                    ->where('enabled', true)
                    ->sortBy('key')
                    ->values()
                    ->map(function ($project) use ($from, $minutes) {
                        return $this->projectSummary($project, $from, $minutes);
                    }),
            ];
        })->values();

        return response()->json([
            'minutes' => $minutes,
            'generated_at' => now()->toIso8601String(),
            'hosts' => $payload,
        ]);
    }

    private function projectSummary($project, $from, int $minutes): array
    {
        $now = now();
        $staleAt = $now->copy()->subMinutes(2);
        $host = $project->relationLoaded('host')
            ? $project->host
            : $project->host()->with('agents')->first();
        $agent = $host && $host->relationLoaded('agents')
            ? $host->agents->sortByDesc(function ($item) {
                return $item->last_seen_at ? $item->last_seen_at->timestamp : 0;
            })->first()
            : null;
        $value = static function ($source, string $field) {
            return $source && isset($source->{$field}) ? $source->{$field} : null;
        };
        $number = static function ($source, string $field) use ($value) {
            $fieldValue = $value($source, $field);
            return $fieldValue === null ? null : (float) $fieldValue;
        };
        $integer = static function ($source, string $field) use ($value) {
            $fieldValue = $value($source, $field);
            return $fieldValue === null ? null : (int) $fieldValue;
        };
        $toIso = static function ($date) {
            return $date ? Carbon::parse($date)->toIso8601String() : null;
        };
        $rate = static function ($numerator, $denominator) {
            return $numerator !== null && $denominator !== null && (float) $denominator > 0
                ? round(((float) $numerator / (float) $denominator) * 100, 2)
                : null;
        };

        $sample = DB::table('servers_project_samples')
            ->where('project_id', $project->id)
            ->orderByDesc('sampled_at')
            ->first();
        $firstPeriodSample = DB::table('servers_project_samples')
            ->where('project_id', $project->id)
            ->where('sampled_at', '>=', $from)
            ->orderBy('sampled_at')
            ->first();
        $lastPeriodSample = DB::table('servers_project_samples')
            ->where('project_id', $project->id)
            ->where('sampled_at', '>=', $from)
            ->orderByDesc('sampled_at')
            ->first();
        $sampleStats = DB::table('servers_project_samples')
            ->where('project_id', $project->id)
            ->where('sampled_at', '>=', $from)
            ->selectRaw('AVG(cpu_percent) as cpu_average_percent')
            ->selectRaw('MAX(cpu_percent) as cpu_peak_percent')
            ->selectRaw('MAX(memory_rss_bytes) as memory_rss_peak_bytes')
            ->selectRaw('MAX(memory_pss_bytes) as memory_pss_peak_bytes')
            ->selectRaw('MAX(fpm_listen_queue) as fpm_listen_queue_peak')
            ->first();
        $traffic = DB::table('servers_http_buckets')
            ->where('project_id', $project->id)
            ->where('bucket_start', '>=', $from)
            ->selectRaw('COALESCE(SUM(requests_total), 0) as requests_total')
            ->selectRaw('COALESCE(SUM(bucket_seconds), 0) as coverage_seconds')
            ->selectRaw('COALESCE(SUM(status_2xx), 0) as status_2xx')
            ->selectRaw('COALESCE(SUM(status_3xx), 0) as status_3xx')
            ->selectRaw('COALESCE(SUM(status_4xx), 0) as status_4xx')
            ->selectRaw('COALESCE(SUM(status_5xx), 0) as status_5xx')
            ->selectRaw('COALESCE(SUM(status_499), 0) as status_499')
            ->selectRaw('COALESCE(SUM(status_500), 0) as status_500')
            ->selectRaw('COALESCE(SUM(status_502), 0) as status_502')
            ->selectRaw('COALESCE(SUM(status_503), 0) as status_503')
            ->selectRaw('COALESCE(SUM(status_504), 0) as status_504')
            ->selectRaw('COALESCE(SUM(request_bytes), 0) as request_bytes')
            ->selectRaw('COALESCE(SUM(response_bytes), 0) as response_bytes')
            ->selectRaw('COALESCE(SUM(latency_count), 0) as latency_count')
            ->selectRaw('COALESCE(SUM(latency_sum_ms), 0) as latency_sum_ms')
            ->first();
        $latestBucket = DB::table('servers_http_buckets')
            ->where('project_id', $project->id)
            ->where('bucket_start', '>=', $from)
            ->orderByDesc('bucket_start')
            ->first();
        $peakBucket = DB::table('servers_http_buckets')
            ->where('project_id', $project->id)
            ->where('bucket_start', '>=', $from)
            ->orderByDesc('requests_total')
            ->first();
        $latestStorage = DB::table('servers_storage_samples')
            ->where('project_id', $project->id)
            ->orderByDesc('sampled_at')
            ->first();
        $firstPeriodStorage = DB::table('servers_storage_samples')
            ->where('project_id', $project->id)
            ->where('sampled_at', '>=', $from)
            ->orderBy('sampled_at')
            ->first();
        $lastPeriodStorage = DB::table('servers_storage_samples')
            ->where('project_id', $project->id)
            ->where('sampled_at', '>=', $from)
            ->orderByDesc('sampled_at')
            ->first();

        $observedDates = collect([
            $value($sample, 'sampled_at'),
            $value($latestBucket, 'bucket_start'),
            $value($latestStorage, 'sampled_at'),
        ])->filter()->map(function ($date) {
            return Carbon::parse($date);
        });
        $lastObservedAt = $observedDates->sortByDesc(function ($date) {
            return $date->timestamp;
        })->first();
        $health = ! $lastObservedAt
            ? 'no_data'
            : ($lastObservedAt->lessThan($staleAt) ? 'stale' : 'reporting');

        $requestsTotal = (int) $traffic->requests_total;
        $coverageSeconds = (int) $traffic->coverage_seconds;
        $coverageMinutes = $coverageSeconds > 0 ? round($coverageSeconds / 60, 2) : 0;
        $status2xx = (int) $traffic->status_2xx;
        $status3xx = (int) $traffic->status_3xx;
        $status4xx = (int) $traffic->status_4xx;
        $status5xx = (int) $traffic->status_5xx;
        $latencyCount = (int) $traffic->latency_count;
        $responseBytes = (int) $traffic->response_bytes;
        $storageFirstValue = $value($firstPeriodStorage, 'total_bytes')
            ?? $value($firstPeriodSample, 'storage_total_bytes');
        $storageLastValue = $value($lastPeriodStorage, 'total_bytes')
            ?? $value($lastPeriodSample, 'storage_total_bytes');
        $storageGrowth = $storageFirstValue !== null && $storageLastValue !== null
            ? (int) $storageLastValue - (int) $storageFirstValue
            : null;
        $storageBreakdown = $value($latestStorage, 'breakdown');
        if (is_string($storageBreakdown)) {
            $storageBreakdown = json_decode($storageBreakdown, true) ?: [];
        }
        $counterDelta = static function ($first, $last, string $field) use ($integer) {
            $firstValue = $integer($first, $field);
            $lastValue = $integer($last, $field);
            return $firstValue === null || $lastValue === null
                ? null
                : max(0, $lastValue - $firstValue);
        };
        $fpmActive = $integer($sample, 'fpm_active_processes');
        $fpmIdle = $integer($sample, 'fpm_idle_processes');
        $fpmQueue = $integer($sample, 'fpm_listen_queue');
        $fpmMaxQueue = $integer($sample, 'fpm_max_listen_queue');
        $fpmProcessTotal = ($fpmActive ?? 0) + ($fpmIdle ?? 0);

        return [
            'id' => (int) $project->id,
            'key' => $project->key,
            'name' => $project->name,
            'host_key' => $host ? $host->key : null,
            'host_name' => $host ? $host->name : null,
            'hostname' => $host ? $host->hostname : null,
            'path' => $project->path,
            'environment' => $project->environment,
            'client_id' => $project->client_id ? (int) $project->client_id : null,
            'notifications_enabled' => (bool) $project->notifications_enabled,
            'php_version' => $project->php_version,
            'fpm_pool' => $project->fpm_pool,
            'attribution_mode' => $value($sample, 'attribution_mode') ?? $project->attribution_mode,
            'health' => $health,
            'last_sample_at' => $lastObservedAt ? $lastObservedAt->toIso8601String() : null,
            'agent_status' => $agent && $agent->last_seen_at && $agent->last_seen_at->greaterThan($staleAt)
                ? 'healthy'
                : 'stale',
            'agent_last_seen_at' => $agent ? $toIso($agent->last_seen_at) : null,
            'agent_version' => $agent ? $agent->version : null,
            'agent_spool_bytes' => $agent ? (int) $agent->spool_bytes : 0,
            'requests_total' => $requestsTotal,
            'requests_per_minute' => $coverageSeconds > 0
                ? round($requestsTotal / max($coverageMinutes, 1), 2)
                : null,
            'peak_requests_per_minute' => $peakBucket
                ? round(((int) $peakBucket->requests_total * 60) / max((int) $peakBucket->bucket_seconds, 1), 2)
                : null,
            'coverage_minutes' => $coverageMinutes,
            'coverage_percent' => $minutes > 0
                ? min(100, round(($coverageMinutes / $minutes) * 100, 2))
                : null,
            'status_2xx' => $status2xx,
            'status_3xx' => $status3xx,
            'status_4xx' => $status4xx,
            'status_5xx' => $status5xx,
            'status_499' => (int) $traffic->status_499,
            'status_500' => (int) $traffic->status_500,
            'status_502' => (int) $traffic->status_502,
            'status_503' => (int) $traffic->status_503,
            'status_504' => (int) $traffic->status_504,
            'success_rate_percent' => $rate($status2xx, $requestsTotal),
            'availability_percent' => $rate($requestsTotal - $status5xx, $requestsTotal),
            'error_rate_percent' => $rate($status4xx + $status5xx, $requestsTotal),
            'request_bytes' => (int) $traffic->request_bytes,
            'response_bytes' => $responseBytes,
            'average_response_bytes' => $requestsTotal > 0
                ? round($responseBytes / $requestsTotal, 2)
                : null,
            'latency_average_ms' => $latencyCount > 0
                ? round((float) $traffic->latency_sum_ms / $latencyCount, 2)
                : null,
            'p50_ms' => $latestBucket ? $number($latestBucket, 'p50_ms') : null,
            'p95_ms' => $latestBucket ? $number($latestBucket, 'p95_ms') : null,
            'p99_ms' => $latestBucket ? $number($latestBucket, 'p99_ms') : null,
            'cpu_percent' => $number($sample, 'cpu_percent'),
            'cpu_average_percent' => $number($sampleStats, 'cpu_average_percent'),
            'cpu_peak_percent' => $number($sampleStats, 'cpu_peak_percent'),
            'memory_rss_bytes' => $integer($sample, 'memory_rss_bytes'),
            'memory_pss_bytes' => $integer($sample, 'memory_pss_bytes'),
            'memory_rss_peak_bytes' => $integer($sampleStats, 'memory_rss_peak_bytes'),
            'memory_pss_peak_bytes' => $integer($sampleStats, 'memory_pss_peak_bytes'),
            'process_count' => $integer($sample, 'process_count'),
            'fpm_active_processes' => $fpmActive,
            'fpm_idle_processes' => $fpmIdle,
            'fpm_listen_queue' => $fpmQueue,
            'fpm_max_listen_queue' => $fpmMaxQueue,
            'fpm_listen_queue_peak' => $integer($sampleStats, 'fpm_listen_queue_peak'),
            'fpm_queue_percent' => $rate($fpmQueue, $fpmMaxQueue),
            'fpm_utilization_percent' => $fpmActive !== null && $fpmIdle !== null && $fpmProcessTotal > 0
                ? round(($fpmActive / $fpmProcessTotal) * 100, 2)
                : null,
            'fpm_max_children_reached_delta' => $counterDelta(
                $firstPeriodSample,
                $lastPeriodSample,
                'fpm_max_children_reached'
            ),
            'fpm_slow_requests_delta' => $counterDelta(
                $firstPeriodSample,
                $lastPeriodSample,
                'fpm_slow_requests'
            ),
            'storage_total_bytes' => $integer($sample, 'storage_total_bytes')
                ?? $integer($latestStorage, 'total_bytes'),
            'storage_growth_bytes' => $storageGrowth,
            'storage_files' => $integer($sample, 'storage_files')
                ?? $integer($latestStorage, 'files'),
            'storage_directories' => $integer($sample, 'storage_directories')
                ?? $integer($latestStorage, 'directories'),
            'storage_scan_duration_ms' => $integer($sample, 'storage_scan_duration_ms')
                ?? $integer($latestStorage, 'scan_duration_ms'),
            'storage_breakdown' => is_array($storageBreakdown) ? $storageBreakdown : [],
        ];
    }

    private function dashboardFilters(Request $request): array
    {
        $pagination = (array) $request->input('pagination', []);
        $minutes = max(15, min((int) $request->input('minutes', 1440), 43200));
        $perPage = (int) ($pagination['per_page'] ?? 10);
        if (! in_array($perPage, [5, 10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return [
            'minutes' => $minutes,
            'from' => now()->subMinutes($minutes),
            'page' => max(1, (int) ($pagination['page'] ?? 1)),
            'per_page' => $perPage,
            'search' => trim((string) $request->input('search', '')),
            'environment' => trim((string) $request->input('environment', '')),
            'host_key' => trim((string) $request->input('host_key', '')),
            'health' => in_array($request->input('health'), ['reporting', 'stale', 'no_data'], true)
                ? $request->input('health')
                : '',
            'sort_by' => in_array($request->input('sort_by'), [
                'name',
                'host',
                'health',
                'requests_per_minute',
                'availability_percent',
                'p95_ms',
                'status_5xx',
                'cpu_percent',
                'fpm_listen_queue',
                'storage_total_bytes',
            ], true) ? $request->input('sort_by') : 'name',
            'sort_direction' => in_array(strtolower((string) $request->input('sort_direction', 'desc')), ['asc', 'desc'], true)
                ? strtolower((string) $request->input('sort_direction', 'desc'))
                : 'desc',
        ];
    }

    private function projectRows(array $filters)
    {
        $projects = servers_project::with(['host.agents'])
            ->where('enabled', true)
            ->whereHas('host', function ($query) use ($filters) {
                $query->where('enabled', true);
                if ($filters['host_key'] !== '') {
                    $query->where('key', $filters['host_key']);
                }
            });

        if ($filters['environment'] !== '') {
            $projects->where('environment', $filters['environment']);
        }

        return $projects->get()
            ->map(function ($project) use ($filters) {
                return $this->projectSummary($project, $filters['from'], $filters['minutes']);
            })
            ->filter(function (array $project) use ($filters) {
                if ($filters['health'] !== '' && $project['health'] !== $filters['health']) {
                    return false;
                }
                if ($filters['search'] === '') {
                    return true;
                }
                $haystack = Str::lower(implode(' ', array_filter([
                    $project['key'],
                    $project['name'],
                    $project['host_key'],
                    $project['host_name'],
                    $project['hostname'],
                    $project['path'],
                    $project['environment'],
                    $project['client_id'],
                ])));
                return Str::contains($haystack, Str::lower($filters['search']));
            })
            ->sort(function (array $left, array $right) use ($filters) {
                return $this->compareProjectRows($left, $right, $filters);
            })
            ->values();
    }

    private function compareProjectRows(array $left, array $right, array $filters): int
    {
        $leftValue = $this->projectSortValue($left, $filters['sort_by']);
        $rightValue = $this->projectSortValue($right, $filters['sort_by']);
        $leftMissing = $leftValue === null || $leftValue === '';
        $rightMissing = $rightValue === null || $rightValue === '';

        if ($leftMissing !== $rightMissing) {
            return $leftMissing ? 1 : -1;
        }

        if (is_numeric($leftValue) && is_numeric($rightValue)) {
            $comparison = (float) $leftValue <=> (float) $rightValue;
        } else {
            $comparison = strnatcasecmp(Str::lower((string) $leftValue), Str::lower((string) $rightValue));
        }

        if ($comparison === 0 && $filters['sort_by'] !== 'name') {
            $comparison = strnatcasecmp(
                Str::lower((string) ($left['name'] ?? '')),
                Str::lower((string) ($right['name'] ?? ''))
            );
        }

        if ($comparison === 0) {
            $comparison = (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
        }

        return $filters['sort_direction'] === 'desc' ? -$comparison : $comparison;
    }

    private function projectSortValue(array $project, string $sortBy)
    {
        if ($sortBy === 'host') {
            return trim(($project['host_name'] ?? '') . ' ' . ($project['environment'] ?? ''));
        }

        if ($sortBy === 'health') {
            return [
                'reporting' => 0,
                'stale' => 1,
                'no_data' => 2,
            ][$project['health'] ?? ''] ?? 3;
        }

        return $project[$sortBy] ?? null;
    }

    private function projectTotals($rows): array
    {
        return [
            'projects' => $rows->count(),
            'reporting' => $rows->where('health', 'reporting')->count(),
            'stale' => $rows->where('health', 'stale')->count(),
            'no_data' => $rows->where('health', 'no_data')->count(),
            'requests_total' => (int) $rows->sum('requests_total'),
            'status_5xx' => (int) $rows->sum('status_5xx'),
            'response_bytes' => (int) $rows->sum('response_bytes'),
        ];
    }
}
