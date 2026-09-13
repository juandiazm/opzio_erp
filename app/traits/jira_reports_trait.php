<?php

namespace App\traits;

use App\Models\jira_issue;
use App\Models\jira_project;
use App\Models\jira_report;
use App\Models\jira_report_recurrence;
use App\Services\Jira\jira_report_recurrence_service;
use App\Services\Jira\jira_report_service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

trait jira_reports_trait
{
    use mail_trait;
    use open_ia_trait;
    use pdf_trait;

    public function Jira_ReportsData(Request $request): array
    {
        $this->Jira_Authorize();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'project_id' => ['nullable', 'integer', 'exists:jira_projects,id'],
            'status' => ['nullable', Rule::in(['generated', 'generating', 'failed'])],
            'intention' => ['nullable', Rule::in(array_keys(jira_report_service::intentions()))],
            'recurrence' => ['nullable', Rule::in(['recurring', 'non_recurring'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([5, 10, 25, 50])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $reports = jira_report::query()
            ->with(['creator', 'project', 'epic', 'recurrence'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('unique_id', 'like', '%'.$search.'%')
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery
                            ->where('project_key', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('epic', fn ($epicQuery) => $epicQuery
                            ->where('issue_key', 'like', '%'.$search.'%')
                            ->orWhere('summary', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['project_id'] ?? null, fn ($query, $projectId) => $query->where('jira_project_id', $projectId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['intention'] ?? null, fn ($query, $intention) => $query->where('intention', $intention))
            ->when(($filters['recurrence'] ?? null) === 'recurring', fn ($query) => $query->whereHas('recurrence'))
            ->when(($filters['recurrence'] ?? null) === 'non_recurring', fn ($query) => $query->whereDoesntHave('recurrence'))
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'reports' => $reports,
            'intentions' => jira_report_service::intentions(),
            'data_sources' => jira_report_service::dataSources(),
        ];
    }

    public function Jira_GenerateReport(Request $request): jira_report
    {
        $this->Jira_Authorize();
        $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'intention' => ['required', Rule::in(array_keys(jira_report_service::intentions()))],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:jira_projects,id'],
            'epic_ids' => ['nullable', 'array'],
            'epic_ids.*' => ['integer', 'exists:jira_issues,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:jira_users,id'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', 'max:120'],
            'jira_project_id' => ['nullable', 'integer', 'exists:jira_projects,id'],
            'jira_epic_issue_id' => ['nullable', 'integer', 'exists:jira_issues,id'],
            'data_sources' => ['required', 'array', 'min:1'],
            'data_sources.*' => [Rule::in(array_keys(jira_report_service::dataSources()))],
            'context_prompt' => ['nullable', 'string', 'max:5000'],
            'recurrence_enabled' => ['nullable', 'boolean'],
            'frequency_value' => ['nullable', 'integer', 'min:1', 'max:365'],
            'frequency_unit' => ['nullable', Rule::in(jira_report_recurrence_service::FREQUENCY_UNITS)],
            'execution_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'range_value' => ['nullable', 'integer', 'min:1', 'max:365'],
            'range_unit' => ['nullable', Rule::in(jira_report_recurrence_service::RANGE_UNITS)],
        ]);
        $criteria = app(jira_report_service::class)->validateCriteria($request->all());
        $this->Jira_ValidateReportScope($criteria);
        $recurrenceData = $request->boolean('recurrence_enabled')
            ? app(jira_report_recurrence_service::class)->normalize($request->all())
            : null;
        $report = jira_report::create([
            'created_by_user_id' => data_get(session('user'), 'id'),
            'jira_connection_id' => $this->Jira_ReportConnectionId($criteria),
            'jira_project_id' => $criteria['jira_project_id'],
            'jira_epic_issue_id' => $criteria['jira_epic_issue_id'],
            'jira_project_ids' => $criteria['project_ids'],
            'jira_epic_issue_ids' => $criteria['epic_ids'],
            'jira_user_ids' => $criteria['user_ids'],
            'jira_statuses' => $criteria['statuses'],
            'title' => $criteria['title'],
            'intention' => $criteria['intention'],
            'from_date' => $criteria['from_date'],
            'to_date' => $criteria['to_date'],
            'data_sources' => $criteria['data_sources'],
            'context_prompt' => $criteria['context_prompt'],
            'status' => 'generating',
        ]);

        $report = $this->Jira_RunReportGeneration($report, $criteria);
        if ($recurrenceData !== null) {
            $recurrence = jira_report_recurrence::create([
                'template_report_id' => $report->id,
                ...$recurrenceData,
                'next_run_at' => app(jira_report_recurrence_service::class)->nextRunAt(
                    now()->startOfDay(),
                    $recurrenceData['frequency_value'],
                    $recurrenceData['frequency_unit'],
                    $recurrenceData['execution_day'],
                ),
            ]);
            $report->update(['recurrence_id' => $recurrence->id, 'recurrence_sequence' => 0]);
        }

        return $report->fresh(['creator', 'project', 'epic', 'recurrence']);
    }

    public function Jira_RegenerateReport(Request $request, string $uniqueId): jira_report
    {
        $this->Jira_Authorize();
        $report = jira_report::where('unique_id', $uniqueId)->firstOrFail();
        $criteria = $this->Jira_ReportCriteria($report);

        return $this->Jira_RunReportGeneration($report, $criteria);
    }

    public function Jira_ReportDetail(Request $request, string $uniqueId): array
    {
        $this->Jira_Authorize();
        $report = jira_report::query()->with(['creator', 'project', 'epic', 'recurrence'])->where('unique_id', $uniqueId)->firstOrFail();
        return ['report' => $report, 'intentions' => jira_report_service::intentions()];
    }

    public function Jira_DeleteReport(Request $request, string $uniqueId): array
    {
        $this->Jira_Authorize();
        jira_report::where('unique_id', $uniqueId)->firstOrFail()->delete();
        return ['message' => 'Reporte eliminado correctamente.'];
    }

    public function Jira_RestoreReport(Request $request, string $uniqueId): array
    {
        $this->Jira_Authorize();
        jira_report::withTrashed()->where('unique_id', $uniqueId)->firstOrFail()->restore();
        return ['message' => 'Reporte restaurado correctamente.'];
    }

    public function Jira_DownloadReportPdf(Request $request, string $uniqueId): string
    {
        $this->Jira_Authorize();
        $report = jira_report::with(['creator', 'project', 'epic'])->where('unique_id', $uniqueId)->firstOrFail();
        abort_unless($report->status === 'generated' && is_array($report->report_data), 409, 'El reporte aun no esta disponible.');
        return Storage::disk('local')->get($this->Jira_EnsureReportPdf($report));
    }

    public function Jira_EmailReport(Request $request, string $uniqueId): array
    {
        $this->Jira_Authorize();
        $report = jira_report::where('unique_id', $uniqueId)->firstOrFail();
        abort_unless($report->status === 'generated' && is_array($report->report_data), 409, 'El reporte aun no esta disponible.');
        $data = $request->validate(['recipients' => ['required', 'string', 'max:2000']]);
        $recipients = collect(preg_split('/[,;\r\n]+/', (string) $data['recipients']) ?: [])
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->take(10)
            ->map(fn (string $email): array => ['address' => $email, 'name' => null])
            ->values()
            ->all();
        if ($recipients === []) {
            throw ValidationException::withMessages(['recipients' => 'Escribe al menos un correo valido.']);
        }
        $sessionUser = session('user');
        $replyToEmail = trim((string) data_get($sessionUser, 'email', ''));
        $replyToName = trim((string) data_get($sessionUser, 'complete_name', ''));
        if ($replyToName === '') {
            $replyToName = trim((string) data_get($sessionUser, 'name', '').' '.(string) (data_get($sessionUser, 'lastname') ?: data_get($sessionUser, 'last_name', '')));
        }
        $replyTo = filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)
            ? ['address' => $replyToEmail, 'name' => $replyToName ?: $replyToEmail]
            : $this->Mail_GetReplyTo();
        $relativePath = $this->Jira_EnsureReportPdf($report);
        $mailResponse = $this->SendMail_attach_array(
            ['subject' => $report->title],
            $recipients,
            'mail.reports.jira',
            [
                'report_title' => $report->title,
                'period' => $report->from_date->format('d/m/Y').' - '.$report->to_date->format('d/m/Y'),
                'generated_at' => ($report->generated_at ?: now())->format('d/m/Y H:i'),
            ],
            ['path' => Storage::disk('local')->path($relativePath), 'name' => Str::slug($report->title).'.pdf'],
            null,
            null,
            $replyTo,
        );
        if (($mailResponse['status'] ?? 0) === 1) {
            $report->update(['last_emailed_at' => now()]);
        }

        return $mailResponse;
    }

    public function Jira_ProcessReportRecurrences(?Carbon $now = null): array
    {
        $now = ($now ?: now())->copy();
        $service = app(jira_report_recurrence_service::class);
        $processed = 0;
        $generated = 0;
        $failed = 0;

        $ids = jira_report_recurrence::query()
            ->where('active', true)
            ->where('next_run_at', '<=', $now)
            ->pluck('id');

        foreach ($ids as $recurrenceId) {
            $recurrence = DB::transaction(function () use ($recurrenceId, $now, $service): ?jira_report_recurrence {
                $recurrence = jira_report_recurrence::query()->lockForUpdate()->find($recurrenceId);
                if (! $recurrence || ! $recurrence->active || $recurrence->next_run_at->isFuture()) {
                    return null;
                }

                $nextRun = $service->nextRunAt(
                    $recurrence->next_run_at,
                    $recurrence->frequency_value,
                    $recurrence->frequency_unit,
                    $recurrence->execution_day,
                );
                while ($nextRun->lessThanOrEqualTo($now)) {
                    $nextRun = $service->nextRunAt(
                        $nextRun,
                        $recurrence->frequency_value,
                        $recurrence->frequency_unit,
                        $recurrence->execution_day,
                    );
                }
                $recurrence->update(['next_run_at' => $nextRun, 'last_run_at' => $now]);

                return $recurrence->fresh(['templateReport']);
            });

            if (! $recurrence) {
                continue;
            }
            if (! $recurrence->templateReport) {
                $recurrence->update(['active' => false]);
                continue;
            }

            $processed++;
            $template = $recurrence->templateReport;
            $period = $service->reportPeriod($now, $recurrence->range_value, $recurrence->range_unit);
            $sequence = (int) jira_report::query()->where('recurrence_id', $recurrence->id)->max('recurrence_sequence') + 1;
            $title = $this->Jira_RecurringReportTitle($template->title, $now);
            $criteria = $this->Jira_ReportCriteria($template, [
                'title' => $title,
                'generated_title' => $title,
                'from_date' => $period['from_date'],
                'to_date' => $period['to_date'],
            ]);
            $report = jira_report::create([
                'created_by_user_id' => $template->created_by_user_id,
                'jira_connection_id' => $template->jira_connection_id,
                'jira_project_id' => $criteria['jira_project_id'],
                'jira_epic_issue_id' => $criteria['jira_epic_issue_id'],
                'jira_project_ids' => $criteria['project_ids'],
                'jira_epic_issue_ids' => $criteria['epic_ids'],
                'jira_user_ids' => $criteria['user_ids'],
                'jira_statuses' => $criteria['statuses'],
                'title' => $title,
                'intention' => $criteria['intention'],
                'from_date' => $criteria['from_date'],
                'to_date' => $criteria['to_date'],
                'data_sources' => $criteria['data_sources'],
                'context_prompt' => $criteria['context_prompt'],
                'recurrence_id' => $recurrence->id,
                'recurrence_sequence' => $sequence,
                'status' => 'generating',
            ]);

            try {
                $this->Jira_RunReportGeneration($report, $criteria);
                $generated++;
            } catch (Throwable $exception) {
                $failed++;
                logger()->error('No fue posible generar una recurrencia Jira.', [
                    'jira_recurrence_id' => $recurrence->id,
                    'jira_report_id' => $report->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return compact('processed', 'generated', 'failed');
    }

    private function Jira_RunReportGeneration(jira_report $report, array $criteria): jira_report
    {
        $service = app(jira_report_service::class);
        try {
            Storage::disk('local')->delete($this->Jira_ReportPdfPath($report));
            $snapshot = $service->snapshot($criteria);
            $report->update(['status' => 'generating', 'data_snapshot' => $snapshot, 'report_data' => null, 'error_message' => null]);
            $response = $this->OpenIA_MakeQuestion(
                $service->prompt($criteria, $snapshot),
                'gpt-5.6-luna',
                ['purpose' => 'content', 'instructions' => $service->instructions($criteria['intention']), 'max_output_tokens' => $criteria['intention'] === 'client_report' ? 12000 : 7000, 'json_schema' => $service->schema($criteria['intention'])],
            );
            if (($response['status'] ?? 0) !== 1) {
                throw new RuntimeException((string) ($response['message'] ?? 'No fue posible generar el reporte con IA.'));
            }
            $parsed = $service->parseResponse($response);
            if ($parsed === null) {
                throw new RuntimeException('La IA devolvio una respuesta que no se pudo interpretar.');
            }
            $reportData = $service->normalize($parsed, $criteria, $snapshot);
            if (filled($criteria['generated_title'] ?? null)) {
                $reportData['report_title'] = $criteria['generated_title'];
            }
            $report->update([
                'status' => 'generated',
                'report_data' => $reportData,
                'ai_model' => $response['model'] ?? null,
                'ai_response_id' => $response['response_id'] ?? null,
                'generated_at' => now(),
            ]);
            $this->Jira_EnsureReportPdf($report->fresh(['creator', 'project', 'epic']));
        } catch (Throwable $exception) {
            $message = Str::limit(trim($exception->getMessage()), 500, '');
            logger()->error('No fue posible generar un reporte Jira.', ['jira_report_id' => $report->id, 'message' => $message]);
            $report->update(['status' => 'failed', 'error_message' => $message]);
            throw ValidationException::withMessages(['report' => 'No fue posible generar el reporte Jira: '.Str::limit($message, 300, '')]);
        }

        return $report->fresh(['creator', 'project', 'epic', 'recurrence']);
    }

    private function Jira_ReportCriteria(jira_report $report, array $overrides = []): array
    {
        return array_merge([
            'title' => $report->title,
            'intention' => $report->intention,
            'from_date' => $report->from_date->toDateString(),
            'to_date' => $report->to_date->toDateString(),
            'project_ids' => $report->jira_project_ids ?: ($report->jira_project_id ? [$report->jira_project_id] : []),
            'epic_ids' => $report->jira_epic_issue_ids ?: ($report->jira_epic_issue_id ? [$report->jira_epic_issue_id] : []),
            'user_ids' => $report->jira_user_ids ?? [],
            'statuses' => $report->jira_statuses ?? [],
            'jira_project_id' => $report->jira_project_id,
            'jira_epic_issue_id' => $report->jira_epic_issue_id,
            'data_sources' => $report->data_sources ?? [],
            'context_prompt' => $report->context_prompt,
        ], $overrides);
    }

    private function Jira_RecurringReportTitle(string $baseTitle, Carbon $runAt): string
    {
        $suffix = ' - '.$runAt->format('d/m/Y');
        $baseTitle = trim($baseTitle) ?: 'Informe de resultados';

        return Str::limit($baseTitle, 200 - strlen($suffix), '').$suffix;
    }

    private function Jira_ReportPdfPath(jira_report $report): string
    {
        return 'reports/jira/'.$report->unique_id.'.pdf';
    }

    private function Jira_EnsureReportPdf(jira_report $report): string
    {
        $path = $this->Jira_ReportPdfPath($report);
        $disk = Storage::disk('local');
        if ($disk->exists($path) && $disk->size($path) > 0) {
            return $path;
        }

        $pdf = $this->PDF_GenerarPDF('pdf.jira_report', [
            'report' => $report,
            'snapshot' => $report->data_snapshot ?? [],
            'content' => $report->report_data ?? [],
        ]);
        if (! $disk->put($path, $pdf)) {
            throw new RuntimeException('No fue posible guardar el PDF del reporte Jira.');
        }

        return $path;
    }

    private function Jira_ValidateReportScope(array $criteria): void
    {
        $epicIds = $criteria['epic_ids'] ?? ($criteria['jira_epic_issue_id'] !== null ? [$criteria['jira_epic_issue_id']] : []);
        if ($epicIds === []) {
            return;
        }
        $epics = jira_issue::query()->whereIn('id', $epicIds)->get(['id', 'issue_type', 'jira_project_id']);
        if ($epics->count() !== count($epicIds)) {
            throw ValidationException::withMessages(['epic_ids' => 'Una de las epicas seleccionadas no existe.']);
        }
        if ($epics->contains(fn (jira_issue $epic): bool => ! str_contains(strtolower((string) $epic->issue_type), 'epic'))) {
            throw ValidationException::withMessages(['epic_ids' => 'Uno de los issues seleccionados no es una epica.']);
        }
        $projectIds = $criteria['project_ids'] ?? ($criteria['jira_project_id'] !== null ? [$criteria['jira_project_id']] : []);
        if ($projectIds !== [] && $epics->contains(fn (jira_issue $epic): bool => ! in_array((int) $epic->jira_project_id, $projectIds, true))) {
            throw ValidationException::withMessages(['epic_ids' => 'Una de las epicas no pertenece a los proyectos seleccionados.']);
        }
    }

    private function Jira_ReportConnectionId(array $criteria): ?int
    {
        $projectIds = $criteria['project_ids'] ?? ($criteria['jira_project_id'] !== null ? [$criteria['jira_project_id']] : []);
        if ($projectIds === []) {
            return null;
        }
        $connectionIds = jira_project::query()
            ->whereIn('id', $projectIds)
            ->pluck('jira_connection_id')
            ->filter()
            ->unique()
            ->values();

        return $connectionIds->count() === 1 ? (int) $connectionIds->first() : null;
    }
}
