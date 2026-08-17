<?php

namespace App\Exportable;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class servers_projects implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    private Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Servidores';
    }

    public function headings(): array
    {
        return [
            'Proyecto',
            'Clave proyecto',
            'Host',
            'Entorno',
            'Ruta',
            'PHP',
            'Pool FPM',
            'Estado',
            'Última captura',
            'Modo atribución',
            'Solicitudes totales',
            'Solicitudes/min',
            'Pico solicitudes/min',
            'Cobertura (min)',
            'Cobertura (%)',
            'Disponibilidad (%)',
            'Éxito 2xx (%)',
            'Error 4xx/5xx (%)',
            '2xx',
            '3xx',
            '4xx',
            '499',
            '5xx',
            '500',
            '502',
            '503',
            '504',
            'Bytes entrada',
            'Bytes salida',
            'Bytes salida/request',
            'Latencia media (ms)',
            'p50 (ms)',
            'p95 (ms)',
            'p99 (ms)',
            'CPU actual (%)',
            'CPU promedio (%)',
            'CPU pico (%)',
            'RAM RSS actual',
            'RAM RSS pico',
            'Procesos',
            'FPM activos',
            'FPM inactivos',
            'FPM queue',
            'FPM queue pico',
            'FPM queue (%)',
            'FPM utilización (%)',
            'Incremento max children',
            'Incremento solicitudes lentas',
            'Storage total',
            'Crecimiento storage',
            'Archivos',
            'Directorios',
            'Duración escaneo (ms)',
            'Desglose storage',
            'Estado agente',
            'Versión agente',
            'Último heartbeat',
            'Spool (bytes)',
        ];
    }

    public function collection(): Collection
    {
        return $this->rows->map(function (array $row) {
            return [
                $row['name'],
                $row['key'],
                $row['host_name'] ?? $row['hostname'],
                $row['environment'],
                $row['path'],
                $row['php_version'],
                $row['fpm_pool'],
                $row['health'],
                $row['last_sample_at'],
                $row['attribution_mode'],
                $row['requests_total'],
                $row['requests_per_minute'],
                $row['peak_requests_per_minute'],
                $row['coverage_minutes'],
                $row['coverage_percent'],
                $row['availability_percent'],
                $row['success_rate_percent'],
                $row['error_rate_percent'],
                $row['status_2xx'],
                $row['status_3xx'],
                $row['status_4xx'],
                $row['status_499'],
                $row['status_5xx'],
                $row['status_500'],
                $row['status_502'],
                $row['status_503'],
                $row['status_504'],
                $row['request_bytes'],
                $row['response_bytes'],
                $row['average_response_bytes'],
                $row['latency_average_ms'],
                $row['p50_ms'],
                $row['p95_ms'],
                $row['p99_ms'],
                $row['cpu_percent'],
                $row['cpu_average_percent'],
                $row['cpu_peak_percent'],
                $row['memory_rss_bytes'],
                $row['memory_rss_peak_bytes'],
                $row['process_count'],
                $row['fpm_active_processes'],
                $row['fpm_idle_processes'],
                $row['fpm_listen_queue'],
                $row['fpm_listen_queue_peak'],
                $row['fpm_queue_percent'],
                $row['fpm_utilization_percent'],
                $row['fpm_max_children_reached_delta'],
                $row['fpm_slow_requests_delta'],
                $row['storage_total_bytes'],
                $row['storage_growth_bytes'],
                $row['storage_files'],
                $row['storage_directories'],
                $row['storage_scan_duration_ms'],
                json_encode($row['storage_breakdown'], JSON_UNESCAPED_UNICODE),
                $row['agent_status'],
                $row['agent_version'],
                $row['agent_last_seen_at'],
                $row['agent_spool_bytes'],
            ];
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);
            },
        ];
    }
}