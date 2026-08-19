<?php

namespace App\Services\Servers;

use App\Domain\Servers\Models\servers_project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class server_monthly_report_service
{
    public function build(servers_project $project, Carbon $from, Carbon $to): array
    {
        $project->loadMissing('host');

        $traffic = DB::table('servers_http_buckets')
            ->where('project_id', $project->id)
            ->whereBetween('bucket_start', [$from, $to])
            ->selectRaw('COALESCE(SUM(requests_total), 0) as requests_total')
            ->selectRaw('COALESCE(SUM(bucket_seconds), 0) as coverage_seconds')
            ->selectRaw('COALESCE(SUM(status_2xx), 0) as status_2xx')
            ->selectRaw('COALESCE(SUM(status_3xx), 0) as status_3xx')
            ->selectRaw('COALESCE(SUM(status_4xx), 0) as status_4xx')
            ->selectRaw('COALESCE(SUM(status_5xx), 0) as status_5xx')
            ->selectRaw('COALESCE(SUM(request_bytes), 0) as request_bytes')
            ->selectRaw('COALESCE(SUM(response_bytes), 0) as response_bytes')
            ->selectRaw('COALESCE(SUM(latency_count), 0) as latency_count')
            ->selectRaw('COALESCE(SUM(latency_sum_ms), 0) as latency_sum_ms')
            ->selectRaw('AVG(p95_ms) as p95_average_ms')
            ->selectRaw('MAX(p95_ms) as p95_peak_ms')
            ->first();

        $bucketCount = DB::table('servers_http_buckets')
            ->where('project_id', $project->id)
            ->whereBetween('bucket_start', [$from, $to])
            ->count();

        $samples = DB::table('servers_project_samples')
            ->where('project_id', $project->id)
            ->whereBetween('sampled_at', [$from, $to]);
        $sampleStats = (clone $samples)
            ->selectRaw('COUNT(*) as samples_count')
            ->selectRaw('AVG(cpu_percent) as cpu_average_percent')
            ->selectRaw('MAX(cpu_percent) as cpu_peak_percent')
            ->selectRaw('MAX(memory_rss_bytes) as memory_rss_peak_bytes')
            ->selectRaw('MAX(memory_pss_bytes) as memory_pss_peak_bytes')
            ->selectRaw('AVG(fpm_listen_queue) as fpm_queue_average')
            ->selectRaw('MAX(fpm_listen_queue) as fpm_queue_peak')
            ->selectRaw('MAX(fpm_max_listen_queue) as fpm_max_queue')
            ->first();
        $latestSample = (clone $samples)->orderByDesc('sampled_at')->first();

        $storageSamples = DB::table('servers_storage_samples')
            ->where('project_id', $project->id)
            ->whereBetween('sampled_at', [$from, $to]);
        $firstStorage = (clone $storageSamples)->orderBy('sampled_at')->first();
        $latestStorage = (clone $storageSamples)->orderByDesc('sampled_at')->first();

        $eventsBySeverity = DB::table('servers_events')
            ->where('project_id', $project->id)
            ->whereBetween('occurred_at', [$from, $to])
            ->select('severity')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->map(fn ($total) => (int) $total)
            ->all();

        $requestsTotal = $this->integer($traffic->requests_total);
        $coverageSeconds = $this->integer($traffic->coverage_seconds);
        $coverageMinutes = $coverageSeconds > 0 ? round($coverageSeconds / 60, 2) : 0;
        $status2xx = $this->integer($traffic->status_2xx);
        $status3xx = $this->integer($traffic->status_3xx);
        $status4xx = $this->integer($traffic->status_4xx);
        $status5xx = $this->integer($traffic->status_5xx);
        $latencyCount = $this->integer($traffic->latency_count);
        $availability = $requestsTotal > 0
            ? round((($requestsTotal - $status5xx) / $requestsTotal) * 100, 2)
            : null;
        $errorRate = $requestsTotal > 0
            ? round((($status4xx + $status5xx) / $requestsTotal) * 100, 2)
            : null;
        $latencyAverage = $latencyCount > 0
            ? round(((float) ($traffic->latency_sum_ms ?? 0)) / $latencyCount, 2)
            : null;
        $periodSeconds = max(1, $from->diffInSeconds($to));
        $coveragePercent = $coverageSeconds > 0
            ? min(100, round(($coverageSeconds / $periodSeconds) * 100, 2))
            : 0;
        $storageFirstBytes = $this->value($firstStorage, 'total_bytes');
        $storageLatestBytes = $this->value($latestStorage, 'total_bytes');
        $storageGrowth = $storageFirstBytes !== null && $storageLatestBytes !== null
            ? $storageLatestBytes - $storageFirstBytes
            : null;
        $lastObservedAt = collect([
            $this->value($latestSample, 'sampled_at'),
            $this->value($latestStorage, 'sampled_at'),
        ])->filter()->map(fn ($date) => Carbon::parse($date))->sortByDesc(fn ($date) => $date->timestamp)->first();
        $p95Average = $this->nullableNumber($traffic->p95_average_ms);
        $p95Peak = $this->nullableNumber($traffic->p95_peak_ms);
        $cpuAverage = $this->nullableNumber($sampleStats->cpu_average_percent);
        $cpuPeak = $this->nullableNumber($sampleStats->cpu_peak_percent);
        $fpmQueuePeak = $this->nullableInteger($sampleStats->fpm_queue_peak);
        $fpmMaxQueue = $this->nullableInteger($sampleStats->fpm_max_queue);
        $notificationName = trim((string) $project->notification_name);
        $displayName = $notificationName !== '' ? $notificationName : $project->name;

        $report = [
            'project' => [
                'id' => (int) $project->id,
                'name' => $project->name,
            'display_name' => $displayName,
            'notification_name' => $notificationName !== '' ? $notificationName : null,
                'key' => $project->key,
                'host_name' => $project->host ? $project->host->name : null,
                'hostname' => $project->host ? $project->host->hostname : null,
                'environment' => $project->environment,
                'path' => $project->path,
                'php_version' => $project->php_version,
                'fpm_pool' => $project->fpm_pool,
            ],
            'period' => [
                'key' => $from->format('Y-m'),
                'label' => $this->periodLabel($from),
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            'headline' => [
                'availability_percent' => $availability,
                'requests_total' => $requestsTotal,
                'p95_average_ms' => $p95Average,
                'status_5xx' => $status5xx,
                'cpu_average_percent' => $cpuAverage,
                'storage_total_bytes' => $storageLatestBytes,
            ],
            'metrics' => [
                'traffic' => [
                    'requests_total' => $requestsTotal,
                    'requests_per_minute' => $coverageMinutes > 0 ? round($requestsTotal / $coverageMinutes, 2) : null,
                    'coverage_minutes' => $coverageMinutes,
                    'coverage_percent' => $coveragePercent,
                    'request_bytes' => $this->integer($traffic->request_bytes),
                    'response_bytes' => $this->integer($traffic->response_bytes),
                    'buckets' => $bucketCount,
                ],
                'reliability' => [
                    'availability_percent' => $availability,
                    'status_2xx' => $status2xx,
                    'status_3xx' => $status3xx,
                    'status_4xx' => $status4xx,
                    'status_5xx' => $status5xx,
                    'error_rate_percent' => $errorRate,
                ],
                'performance' => [
                    'latency_average_ms' => $latencyAverage,
                    'p95_average_ms' => $p95Average,
                    'p95_peak_ms' => $p95Peak,
                ],
                'capacity' => [
                    'cpu_average_percent' => $cpuAverage,
                    'cpu_peak_percent' => $cpuPeak,
                    'memory_rss_peak_bytes' => $this->nullableInteger($sampleStats->memory_rss_peak_bytes),
                    'memory_pss_peak_bytes' => $this->nullableInteger($sampleStats->memory_pss_peak_bytes),
                    'fpm_queue_average' => $this->nullableNumber($sampleStats->fpm_queue_average),
                    'fpm_queue_peak' => $fpmQueuePeak,
                    'fpm_max_queue' => $fpmMaxQueue,
                    'storage_total_bytes' => $storageLatestBytes,
                    'storage_growth_bytes' => $storageGrowth,
                    'storage_files' => $this->nullableInteger($this->value($latestStorage, 'files')),
                    'storage_directories' => $this->nullableInteger($this->value($latestStorage, 'directories')),
                ],
                'operations' => [
                    'samples' => $this->integer($sampleStats->samples_count),
                    'events_total' => array_sum($eventsBySeverity),
                    'events_by_severity' => $eventsBySeverity,
                    'last_observed_at' => $lastObservedAt ? $lastObservedAt->toIso8601String() : null,
                ],
            ],
        ];

        $report['stakeholder_summary'] = $this->stakeholderSummary($report);
        $report['recommendations'] = $this->recommendations($report);

        return $report;
    }

    private function stakeholderSummary(array $report): array
    {
        $metrics = $report['metrics'];
        $availability = $metrics['reliability']['availability_percent'];
        $p95 = $metrics['performance']['p95_average_ms'];
        $errors = $metrics['reliability']['status_5xx'];
        $cpu = $metrics['capacity']['cpu_average_percent'];

        return [
            [
                'label' => 'Disponibilidad del servicio',
                'value' => $this->percent($availability),
                'tone' => $this->toneForAvailability($availability),
                'message' => $availability === null
                    ? 'No hubo mediciones suficientes de disponibilidad en este período.'
                    : ($availability >= 99.9 ? 'El servicio mantuvo una disponibilidad sobresaliente durante el período.' : ($availability >= 99 ? 'El servicio se mantuvo disponible y estable durante el período.' : 'Se registraron intervalos de disponibilidad reducida durante el período.')),
            ],
            [
                'label' => 'Experiencia de respuesta',
                'value' => $this->milliseconds($p95),
                'tone' => $p95 === null ? 'neutral' : ($p95 <= 300 ? 'good' : ($p95 <= 800 ? 'watch' : 'risk')),
                'message' => $p95 === null
                    ? 'No hubo mediciones suficientes de latencia en este período.'
                    : ($p95 <= 300 ? 'Las solicitudes respondieron con agilidad durante el período.' : ($p95 <= 800 ? 'La respuesta se mantuvo dentro de los tiempos observados para la operación.' : 'Se registraron picos de latencia durante el período.')),
            ],
            [
                'label' => 'Errores que afectan al usuario',
                'value' => $this->number($errors),
                'tone' => $errors === 0 ? 'good' : 'watch',
                'message' => $errors === 0 ? 'No se registraron respuestas 5xx durante el período.' : 'Se registraron respuestas 5xx durante el período.',
            ],
            [
                'label' => 'Uso promedio de CPU',
                'value' => $this->percent($cpu),
                'tone' => $cpu === null ? 'neutral' : ($cpu < 70 ? 'good' : ($cpu < 85 ? 'watch' : 'risk')),
                'message' => $cpu === null ? 'No hubo mediciones suficientes de capacidad.' : ($cpu < 70 ? 'El consumo promedio de CPU se mantuvo contenido.' : 'El consumo de CPU reflejó los momentos de mayor actividad.'),
            ],
        ];
    }

    private function recommendations(array $report): array
    {
        $metrics = $report['metrics'];
        $recommendations = [];
        if ($metrics['traffic']['requests_total'] === 0) {
            $recommendations[] = 'No se registró tráfico en las ventanas observadas durante el período.';
        }
        if ($metrics['reliability']['availability_percent'] !== null && $metrics['reliability']['availability_percent'] < 99) {
            $recommendations[] = 'Se registraron intervalos de disponibilidad reducida durante el período.';
        }
        if ($metrics['performance']['p95_peak_ms'] !== null && $metrics['performance']['p95_peak_ms'] > 800) {
            $recommendations[] = 'Se registraron picos de latencia durante el período.';
        }
        if (($metrics['capacity']['fpm_queue_peak'] ?? 0) > 0) {
            $recommendations[] = 'Se registró actividad en la cola PHP-FPM durante el período.';
        }
        if ($metrics['capacity']['storage_growth_bytes'] !== null && $metrics['capacity']['storage_growth_bytes'] > 0) {
            $recommendations[] = 'El almacenamiento registró crecimiento durante el período.';
        }

        return $recommendations ?: ['El proyecto mantuvo un comportamiento estable según las métricas disponibles.'];
    }

    private function periodLabel(Carbon $date): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($months[(int) $date->format('n')] ?? $date->format('F')).' '.$date->format('Y');
    }

    private function toneForAvailability($value): string
    {
        if ($value === null) {
            return 'neutral';
        }
        return $value >= 99.9 ? 'good' : ($value >= 99 ? 'watch' : 'risk');
    }

    private function percent($value): string
    {
        return $value === null ? '-' : number_format((float) $value, 2, ',', '.').'%';
    }

    private function milliseconds($value): string
    {
        return $value === null ? '-' : number_format((float) $value, 0, ',', '.').' ms';
    }

    private function number($value): string
    {
        return number_format((float) ($value ?? 0), 0, ',', '.');
    }

    private function integer($value): int
    {
        return (int) ($value ?? 0);
    }

    private function nullableInteger($value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function nullableNumber($value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }

    private function value($source, string $field)
    {
        return $source && isset($source->{$field}) ? $source->{$field} : null;
    }
}
