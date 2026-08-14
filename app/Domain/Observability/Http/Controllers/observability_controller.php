<?php

namespace App\Domain\Observability\Http\Controllers;

use App\Domain\Observability\Models\observability_agent;
use App\Domain\Observability\Models\observability_event;
use App\Domain\Observability\Models\observability_host_sample;
use App\Domain\Observability\Models\observability_http_bucket;
use App\Domain\Observability\Models\observability_ingest_batch;
use App\Domain\Observability\Models\observability_project_sample;
use App\Domain\Observability\Models\observability_storage_sample;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class observability_controller extends Controller
{
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'opzio-erp-observability',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function config(Request $request)
    {
        $agentId = $request->header('X-Opzio-Observer-Agent');

        if (empty($agentId)) {
            return response()->json(['message' => 'Observer agent header is required.'], 422);
        }

        $agent = $this->findAgent($agentId);

        if (! $agent) {
            return response()->json(['message' => 'Observer agent is not registered or disabled.'], 404);
        }

        if (! $agent->host || ! $agent->host->enabled) {
            return response()->json(['message' => 'Observer host is disabled.'], 503);
        }

        $projects = $agent->host->projects()
            ->where('enabled', true)
            ->orderBy('key')
            ->get()
            ->map(function ($project) {
                return [
                    'key' => $project->key,
                    'name' => $project->name,
                    'path' => $project->path,
                    'environment' => $project->environment,
                    'php_version' => $project->php_version,
                    'fpm_pool' => $project->fpm_pool,
                    'fpm_status_url' => $project->fpm_status_url,
                    'nginx_access_log' => $project->nginx_access_log,
                    'nginx_error_log' => $project->nginx_error_log,
                    'attribution_mode' => $project->attribution_mode,
                    'metadata' => $project->metadata,
                ];
            })
            ->values();

        return response()->json([
            'version' => (int) $agent->host->config_version,
            'agent_id' => $agent->agent_id,
            'host' => [
                'key' => $agent->host->key,
                'name' => $agent->host->name,
                'hostname' => $agent->host->hostname,
                'environment' => $agent->host->environment,
            ],
            'projects' => $projects,
        ]);
    }

    public function ingest(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|string|max:100',
            'batch_id' => 'required|string|max:100',
            'captured_at' => 'required|date',
            'config_version' => 'nullable|integer|min:0',
            'host' => 'nullable|array',
            'host.sampled_at' => 'nullable|date',
            'projects' => 'required|array|max:100',
            'projects.*.key' => 'required|string|max:100',
            'projects.*.sampled_at' => 'nullable|date',
            'projects.*.attribution_mode' => 'nullable|in:approximate,pool,cgroup',
            'http_buckets' => 'nullable|array|max:5000',
            'http_buckets.*.project_key' => 'required|string|max:100',
            'http_buckets.*.bucket_start' => 'required|date',
            'storage' => 'nullable|array|max:100',
            'storage.*.project_key' => 'required|string|max:100',
            'storage.*.sampled_at' => 'nullable|date',
            'events' => 'nullable|array|max:500',
            'events.*.project_key' => 'nullable|string|max:100',
            'events.*.event_type' => 'required|string|max:100',
            'events.*.severity' => 'nullable|in:info,warning,critical',
            'events.*.occurred_at' => 'nullable|date',
        ]);

        $payload = $request->all();
        $agent = $this->findAgent($validated['agent_id']);

        if (! $agent) {
            return response()->json(['message' => 'Observer agent is not registered or disabled.'], 404);
        }

        if (! $agent->host || ! $agent->host->enabled) {
            return response()->json(['message' => 'Observer host is disabled.'], 503);
        }

        $payloadHash = hash('sha256', $request->getContent());
        $existingBatch = observability_ingest_batch::where('batch_id', $validated['batch_id'])->first();

        if ($existingBatch) {
            if ($existingBatch->payload_hash !== $payloadHash || $existingBatch->agent_id !== $agent->agent_id) {
                return response()->json(['message' => 'Batch id was already used with another payload.'], 409);
            }

            return response()->json([
                'accepted' => true,
                'duplicate' => true,
                'batch_id' => $validated['batch_id'],
            ], 202);
        }

        $projectReferences = collect($payload['projects'] ?? [])
            ->pluck('key')
            ->merge(collect($payload['http_buckets'] ?? [])->pluck('project_key'))
            ->merge(collect($payload['storage'] ?? [])->pluck('project_key'))
            ->merge(collect($payload['events'] ?? [])->pluck('project_key'))
            ->filter()
            ->unique()
            ->values();

        $projects = $agent->host->projects()
            ->where('enabled', true)
            ->whereIn('key', $projectReferences->all())
            ->get()
            ->keyBy('key');

        $unknownProjects = $projectReferences->diff($projects->keys())->values();

        if ($unknownProjects->isNotEmpty()) {
            return response()->json([
                'message' => 'Payload contains projects outside the active registry.',
                'projects' => $unknownProjects->all(),
            ], 422);
        }

        DB::transaction(function () use ($agent, $payload, $validated, $payloadHash, $projects) {
            observability_ingest_batch::create([
                'batch_id' => $validated['batch_id'],
                'agent_id' => $agent->agent_id,
                'captured_at' => $validated['captured_at'],
                'payload_hash' => $payloadHash,
                'accepted_at' => now(),
            ]);

            if (! empty($payload['host'])) {
                $host = $payload['host'];
                observability_host_sample::create([
                    'host_id' => $agent->host_id,
                    'agent_id' => $agent->agent_id,
                    'sampled_at' => $host['sampled_at'] ?? $validated['captured_at'],
                    'cpu_percent' => $host['cpu_percent'] ?? null,
                    'load1' => $host['load1'] ?? null,
                    'load5' => $host['load5'] ?? null,
                    'load15' => $host['load15'] ?? null,
                    'memory_total_bytes' => $host['memory_total_bytes'] ?? null,
                    'memory_available_bytes' => $host['memory_available_bytes'] ?? null,
                    'swap_total_bytes' => $host['swap_total_bytes'] ?? null,
                    'swap_free_bytes' => $host['swap_free_bytes'] ?? null,
                    'network_rx_bytes' => $host['network_rx_bytes'] ?? null,
                    'network_tx_bytes' => $host['network_tx_bytes'] ?? null,
                    'disk_total_bytes' => $host['disk_total_bytes'] ?? null,
                    'disk_free_bytes' => $host['disk_free_bytes'] ?? null,
                    'disk_used_bytes' => $host['disk_used_bytes'] ?? null,
                    'disk_used_percent' => $host['disk_used_percent'] ?? null,
                    'metadata' => $host['metadata'] ?? null,
                ]);
            }

            foreach ($payload['projects'] ?? [] as $sample) {
                $project = $projects->get($sample['key']);
                observability_project_sample::create([
                    'project_id' => $project->id,
                    'agent_id' => $agent->agent_id,
                    'sampled_at' => $sample['sampled_at'] ?? $validated['captured_at'],
                    'attribution_mode' => $sample['attribution_mode'] ?? $project->attribution_mode,
                    'cpu_percent' => $sample['cpu_percent'] ?? null,
                    'memory_rss_bytes' => $sample['memory_rss_bytes'] ?? null,
                    'memory_pss_bytes' => $sample['memory_pss_bytes'] ?? null,
                    'process_count' => $sample['process_count'] ?? null,
                    'fpm_active_processes' => $sample['fpm_active_processes'] ?? null,
                    'fpm_idle_processes' => $sample['fpm_idle_processes'] ?? null,
                    'fpm_listen_queue' => $sample['fpm_listen_queue'] ?? null,
                    'fpm_max_listen_queue' => $sample['fpm_max_listen_queue'] ?? null,
                    'fpm_max_children_reached' => $sample['fpm_max_children_reached'] ?? null,
                    'fpm_slow_requests' => $sample['fpm_slow_requests'] ?? null,
                    'storage_total_bytes' => $sample['storage_total_bytes'] ?? null,
                    'storage_files' => $sample['storage_files'] ?? null,
                    'storage_directories' => $sample['storage_directories'] ?? null,
                    'storage_scan_duration_ms' => $sample['storage_scan_duration_ms'] ?? null,
                    'metadata' => $sample['metadata'] ?? null,
                ]);
            }

            foreach ($payload['http_buckets'] ?? [] as $bucket) {
                $project = $projects->get($bucket['project_key']);
                observability_http_bucket::create([
                    'project_id' => $project->id,
                    'agent_id' => $agent->agent_id,
                    'bucket_start' => $bucket['bucket_start'],
                    'bucket_seconds' => $bucket['bucket_seconds'] ?? 60,
                    'requests_total' => $bucket['requests_total'] ?? 0,
                    'status_2xx' => $bucket['status_2xx'] ?? 0,
                    'status_3xx' => $bucket['status_3xx'] ?? 0,
                    'status_4xx' => $bucket['status_4xx'] ?? 0,
                    'status_5xx' => $bucket['status_5xx'] ?? 0,
                    'status_499' => $bucket['status_499'] ?? 0,
                    'status_500' => $bucket['status_500'] ?? 0,
                    'status_502' => $bucket['status_502'] ?? 0,
                    'status_503' => $bucket['status_503'] ?? 0,
                    'status_504' => $bucket['status_504'] ?? 0,
                    'request_bytes' => $bucket['request_bytes'] ?? 0,
                    'response_bytes' => $bucket['response_bytes'] ?? 0,
                    'latency_count' => $bucket['latency_count'] ?? 0,
                    'latency_sum_ms' => $bucket['latency_sum_ms'] ?? 0,
                    'p50_ms' => $bucket['p50_ms'] ?? null,
                    'p95_ms' => $bucket['p95_ms'] ?? null,
                    'p99_ms' => $bucket['p99_ms'] ?? null,
                    'metadata' => $bucket['metadata'] ?? null,
                ]);
            }

            foreach ($payload['storage'] ?? [] as $storage) {
                $project = $projects->get($storage['project_key']);
                observability_storage_sample::create([
                    'project_id' => $project->id,
                    'agent_id' => $agent->agent_id,
                    'sampled_at' => $storage['sampled_at'] ?? $validated['captured_at'],
                    'total_bytes' => $storage['total_bytes'] ?? null,
                    'files' => $storage['files'] ?? null,
                    'directories' => $storage['directories'] ?? null,
                    'scan_duration_ms' => $storage['scan_duration_ms'] ?? null,
                    'breakdown' => $storage['breakdown'] ?? null,
                    'metadata' => $storage['metadata'] ?? null,
                ]);
            }

            foreach ($payload['events'] ?? [] as $event) {
                $project = ! empty($event['project_key']) ? $projects->get($event['project_key']) : null;
                observability_event::create([
                    'host_id' => $agent->host_id,
                    'project_id' => $project ? $project->id : null,
                    'agent_id' => $agent->agent_id,
                    'event_type' => $event['event_type'],
                    'severity' => $event['severity'] ?? 'info',
                    'occurred_at' => $event['occurred_at'] ?? $validated['captured_at'],
                    'message' => $event['message'] ?? null,
                    'context' => $event['context'] ?? null,
                ]);
            }

            $agent->last_seen_at = now();
            if (array_key_exists('config_version', $validated)) {
                $agent->config_version = $validated['config_version'];
            }
            $agent->save();
        });

        return response()->json([
            'accepted' => true,
            'duplicate' => false,
            'batch_id' => $validated['batch_id'],
        ], 202);
    }

    public function heartbeat(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|string|max:100',
            'version' => 'nullable|string|max:100',
            'commit_sha' => 'nullable|string|max:100',
            'config_version' => 'nullable|integer|min:0',
            'spool_bytes' => 'nullable|integer|min:0',
            'spool_batches' => 'nullable|integer|min:0',
            'uptime_seconds' => 'nullable|integer|min:0',
            'collection_errors' => 'nullable|array',
        ]);

        $agent = $this->findAgent($validated['agent_id']);

        if (! $agent) {
            return response()->json(['message' => 'Observer agent is not registered or disabled.'], 404);
        }

        $agent->fill([
            'version' => $validated['version'] ?? null,
            'commit_sha' => $validated['commit_sha'] ?? null,
            'config_version' => $validated['config_version'] ?? null,
            'last_seen_at' => now(),
            'spool_bytes' => $validated['spool_bytes'] ?? 0,
            'spool_batches' => $validated['spool_batches'] ?? 0,
            'uptime_seconds' => $validated['uptime_seconds'] ?? 0,
            'collection_errors' => $validated['collection_errors'] ?? null,
        ]);
        $agent->save();

        return response()->noContent();
    }

    private function findAgent(string $agentId): ?observability_agent
    {
        return observability_agent::with('host')
            ->where('agent_id', $agentId)
            ->where('enabled', true)
            ->first();
    }
}
