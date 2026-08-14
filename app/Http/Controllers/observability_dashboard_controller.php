<?php

namespace App\Http\Controllers;

use App\Domain\Observability\Models\observability_host;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class observability_dashboard_controller extends Controller
{
    public function page()
    {
        return view('erp.observability');
    }

    public function summary(Request $request)
    {
        $minutes = (int) $request->input('minutes', 1440);
        $minutes = max(15, min($minutes, 43200));
        $from = now()->subMinutes($minutes);
        $staleAt = now()->subMinutes(2);
        $hosts = observability_host::with(['projects', 'agents'])
            ->where('enabled', true)
            ->orderBy('key')
            ->get();

        $payload = $hosts->map(function ($host) use ($from, $staleAt, $minutes) {
            $agent = $host->agents
                ->sortByDesc(function ($item) {
                    return $item->last_seen_at ? $item->last_seen_at->timestamp : 0;
                })
                ->first();
            $hostSample = DB::table('observability_host_samples')
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
        $sample = DB::table('observability_project_samples')
            ->where('project_id', $project->id)
            ->orderByDesc('sampled_at')
            ->first();
        $traffic = DB::table('observability_http_buckets')
            ->where('project_id', $project->id)
            ->where('bucket_start', '>=', $from)
            ->selectRaw('COALESCE(SUM(requests_total), 0) as requests_total')
            ->selectRaw('COALESCE(SUM(status_5xx), 0) as status_5xx')
            ->selectRaw('COALESCE(SUM(status_502), 0) as status_502')
            ->selectRaw('COALESCE(SUM(status_503), 0) as status_503')
            ->selectRaw('COALESCE(SUM(status_504), 0) as status_504')
            ->first();
        $latestBucket = DB::table('observability_http_buckets')
            ->where('project_id', $project->id)
            ->orderByDesc('bucket_start')
            ->first();

        return [
            'key' => $project->key,
            'name' => $project->name,
            'environment' => $project->environment,
            'attribution_mode' => $sample->attribution_mode ?? $project->attribution_mode,
            'health' => $sample ? 'reporting' : 'no_data',
            'last_sample_at' => $sample->sampled_at ?? null,
            'requests_per_minute' => round((int) $traffic->requests_total / $minutes, 2),
            'status_5xx' => (int) $traffic->status_5xx,
            'status_502' => (int) $traffic->status_502,
            'status_503' => (int) $traffic->status_503,
            'status_504' => (int) $traffic->status_504,
            'p95_ms' => $latestBucket ? (float) $latestBucket->p95_ms : null,
            'cpu_percent' => $sample ? (float) $sample->cpu_percent : null,
            'memory_rss_bytes' => $sample ? (int) $sample->memory_rss_bytes : null,
            'process_count' => $sample ? (int) $sample->process_count : null,
            'fpm_active_processes' => $sample ? (int) $sample->fpm_active_processes : null,
            'fpm_idle_processes' => $sample ? (int) $sample->fpm_idle_processes : null,
            'fpm_listen_queue' => $sample ? (int) $sample->fpm_listen_queue : null,
            'storage_total_bytes' => $sample ? (int) $sample->storage_total_bytes : null,
        ];
    }
}
