<?php

namespace App\Console\Commands;

use App\Domain\Servers\Models\servers_project;
use App\Models\mail_log;
use App\Services\Servers\server_monthly_report_service;
use App\traits\mail_log_trait;
use App\traits\mail_senders_trait;
use App\traits\pdf_trait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class send_servers_monthly_report extends Command
{
    use mail_log_trait, mail_senders_trait, pdf_trait;

    protected $signature = 'servers:send-monthly-report
        {--period= : Periodo en formato YYYY-MM; por defecto, el mes anterior}
        {--date= : Fecha o mes explícito en formato YYYY-MM o YYYY-MM-DD}
        {--project= : ID de un proyecto específico}
        {--force : Reconstruye el PDF y fuerza un nuevo envío aunque ya exista un reporte registrado}
        {--dry-run : Calcula y muestra los proyectos sin generar PDF ni encolar correos}';

    protected $description = 'Encola el reporte mensual de estado de los proyectos de servidores';

    private function randomSendAt(): Carbon
    {
        return Carbon::today(config('app.timezone'))
            ->setTime(7, 0)
            ->addMinutes(random_int(0, 300));
    }

    public function handle(server_monthly_report_service $reportService): int
    {
        try {
            [$from, $to] = $this->periodBounds(
                $this->option('period'),
                $this->option('date')
            );
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $query = servers_project::with([
            'host',
            'notificationRecipients' => function ($query) {
                $query->where('channel', 'email')->orderBy('id');
            },
        ])
            ->where('enabled', true)
            ->where('notifications_enabled', true)
            ->orderBy('id');

        if ($this->option('project') !== null) {
            $projectId = filter_var($this->option('project'), FILTER_VALIDATE_INT);
            if (! $projectId || $projectId < 1) {
                $this->error('La opción --project debe ser un ID válido.');
                return self::FAILURE;
            }
            $query->whereKey($projectId);
        }

        $projects = $query->get();
        $this->info('Procesando reportes de servidores para '.$from->format('Y-m').': '.$projects->count().' proyecto(s).');

        $queued = 0;
        $skipped = 0;
        $failed = 0;
        foreach ($projects as $project) {
            try {
                $result = $this->processProject($project, $from, $to, $reportService);
                if ($result === 'queued') {
                    $queued++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                }
            } catch (\Throwable $exception) {
                $failed++;
                $this->error('No se pudo procesar '.$project->name.': '.$exception->getMessage());
                info('servers:send-monthly-report project error', [
                    'project_id' => $project->id,
                    'period' => $from->format('Y-m'),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->info('Reportes encolados: '.$queued.' | omitidos: '.$skipped.' | fallidos: '.$failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processProject(servers_project $project, Carbon $from, Carbon $to, server_monthly_report_service $reportService): string
    {
        $periodKey = $from->format('Y-m');
        $batch = 'servers-monthly-report:'.$periodKey.':'.$project->id;
        $existingLogs = mail_log::where('notification_batch', $batch)->get();
        if ($existingLogs->isNotEmpty() && ! $this->option('force')) {
            $this->line('Omitido '.$project->name.': el reporte ya fue registrado.');
            return 'skipped';
        }

        $clientRecipients = $project->notificationRecipients
            ->filter(function ($recipient) {
                return filter_var($recipient->value, FILTER_VALIDATE_EMAIL);
            })
            ->unique(function ($recipient) {
                return strtolower(trim($recipient->value));
            })
            ->map(function ($recipient) {
                return [
                    'address' => trim($recipient->value),
                    'name' => $recipient->recipient_name ?: $recipient->value,
                ];
            })
            ->values()
            ->all();

        $report = $reportService->build($project, $from, $to);
        $availability = $report['metrics']['reliability']['availability_percent'];
        $availabilityAlert = $availability !== null && (float) $availability < 90;
        $supportSender = $this->Mail_SenderDirectory()['soporte@opzio.co'];
        $recipients = $availabilityAlert
            ? [[
                'address' => $supportSender['address'],
                'name' => $supportSender['name'],
            ]]
            : $clientRecipients;
        $report['delivery'] = [
            'availability_alert' => $availabilityAlert,
            'availability_percent' => $availability,
            'recipient_type' => $availabilityAlert ? 'support' : 'project',
            'client_recipient_count' => count($clientRecipients),
        ];

        if (! $recipients) {
            $this->line('Omitido '.$project->name.': no hay correos válidos activos.');
            return 'skipped';
        }

        if ($this->option('dry-run')) {
            $destination = $availabilityAlert ? 'soporte@opzio.co (alerta)' : count($recipients).' destinatario(s)';
            $this->line('Vista previa '.$project->name.' -> '.$destination.'.');
            return 'queued';
        }

        $pdf = $this->PDF_GenerarPDF('pdf.servers.monthly_report', [
            'report' => $report,
        ], 'portrait');
        $pdfPath = 'servers/monthly-reports/'.$periodKey.'/project-'.$project->id.'.pdf';
        if (! Storage::disk('local')->put($pdfPath, $pdf)) {
            throw new \RuntimeException('No se pudo guardar el PDF del reporte.');
        }

        if ($this->option('force')) {
            $this->supersedePendingReports($existingLogs, $batch);
        }

        $fromDetails = [
            'address' => 'soporte@opzio.co',
            'name' => 'Soporte Opzio',
        ];
        $subject = $availabilityAlert
            ? 'ALERTA: disponibilidad inferior al 90% - '.$report['project']['display_name'].' - '.$report['period']['label']
            : 'Reporte mensual de estado - '.$report['project']['display_name'].' - '.$report['period']['label'];
        $viewData = [
            'report' => $report,
            '_from' => $fromDetails,
        ];
        $sendAt = $this->randomSendAt();
        $this->MailLog_CreatePending(
            $subject,
            'mail.servers.monthly_report',
            $fromDetails['address'],
            $fromDetails['name'],
            $recipients,
            $viewData,
            [[
                'path' => Storage::disk('local')->path($pdfPath),
                'name' => 'Reporte-servidor-'.$project->key.'-'.$periodKey.'.pdf',
            ]],
            $sendAt,
            $batch
        );

        $this->line('Encolado '.$project->name.' para '.count($recipients).' destinatario(s), programado a las '.$sendAt->format('H:i').'.');
        return 'queued';
    }

    private function supersedePendingReports($logs, string $batch): void
    {
        $pendingLogs = $logs->where('status', 0);
        foreach ($pendingLogs as $log) {
            $log->status = 2;
            $log->error_message = trim((string) $log->error_message)
                ? $log->error_message."\nReemplazado por un envío forzado."
                : 'Reemplazado por un envío forzado.';
            $log->save();
        }

        if ($pendingLogs->isNotEmpty()) {
            info('servers:send-monthly-report pending batch superseded', [
                'batch' => $batch,
                'count' => $pendingLogs->count(),
            ]);
        }
    }

    private function periodBounds(?string $period, ?string $date = null): array
    {
        $period = trim((string) $period);
        $date = trim((string) $date);
        if ($period !== '' && $date !== '') {
            throw new \InvalidArgumentException('Usa --period o --date, no ambos.');
        }

        if ($date !== '') {
            if (preg_match('/^\d{4}-\d{2}$/', $date)) {
                $period = $date;
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                try {
                    $periodDate = Carbon::createFromFormat('!Y-m-d', $date);
                } catch (\Throwable $exception) {
                    throw new \InvalidArgumentException('La fecha no es válida.');
                }
                if (! $periodDate || $periodDate->format('Y-m-d') !== $date) {
                    throw new \InvalidArgumentException('La fecha no es válida.');
                }
                $period = $periodDate->format('Y-m');
            } else {
                throw new \InvalidArgumentException('La fecha debe tener el formato YYYY-MM o YYYY-MM-DD.');
            }
        }

        if ($period === null || trim($period) === '') {
            $from = now()->startOfMonth()->subMonth();
            return [$from->copy()->startOfMonth(), $from->copy()->endOfMonth()];
        }

        $period = trim($period);
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new \InvalidArgumentException('El periodo debe tener el formato YYYY-MM.');
        }

        try {
            $from = Carbon::createFromFormat('!Y-m', $period);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('El periodo no es válido.');
        }

        if (! $from || $from->format('Y-m') !== $period) {
            throw new \InvalidArgumentException('El periodo no es válido.');
        }

        return [$from->copy()->startOfMonth(), $from->copy()->endOfMonth()];
    }
}
