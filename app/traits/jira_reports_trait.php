<?php

namespace App\traits;

use App\Models\jira_issue;
use App\Models\jira_project;
use App\Models\jira_report;
use App\Services\Jira\jira_report_service;
use Illuminate\Http\Request;
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
        return [
            'reports' => jira_report::query()->with(['creator', 'project', 'epic'])->latest('updated_at')->paginate(25),
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
            'jira_project_id' => ['nullable', 'integer', 'exists:jira_projects,id'],
            'jira_epic_issue_id' => ['nullable', 'integer', 'exists:jira_issues,id'],
            'data_sources' => ['required', 'array', 'min:1'],
            'data_sources.*' => [Rule::in(array_keys(jira_report_service::dataSources()))],
            'context_prompt' => ['nullable', 'string', 'max:5000'],
        ]);
        $criteria = app(jira_report_service::class)->validateCriteria($request->all());
        $this->Jira_ValidateReportScope($criteria);
        $report = jira_report::create([
            'created_by_user_id' => data_get(session('user'), 'id'),
            'jira_connection_id' => $this->Jira_ReportConnectionId($criteria),
            'jira_project_id' => $criteria['jira_project_id'],
            'jira_epic_issue_id' => $criteria['jira_epic_issue_id'],
            'title' => $criteria['title'],
            'intention' => $criteria['intention'],
            'from_date' => $criteria['from_date'],
            'to_date' => $criteria['to_date'],
            'data_sources' => $criteria['data_sources'],
            'context_prompt' => $criteria['context_prompt'],
            'status' => 'generating',
        ]);

        return $this->Jira_RunReportGeneration($report, $criteria);
    }

    public function Jira_RegenerateReport(Request $request, string $uniqueId): jira_report
    {
        $this->Jira_Authorize();
        $report = jira_report::where('unique_id', $uniqueId)->firstOrFail();
        $criteria = [
            'title' => $report->title,
            'intention' => $report->intention,
            'from_date' => $report->from_date->toDateString(),
            'to_date' => $report->to_date->toDateString(),
            'jira_project_id' => $report->jira_project_id,
            'jira_epic_issue_id' => $report->jira_epic_issue_id,
            'data_sources' => $report->data_sources ?? [],
            'context_prompt' => $report->context_prompt,
        ];

        return $this->Jira_RunReportGeneration($report, $criteria);
    }

    public function Jira_ReportDetail(Request $request, string $uniqueId): array
    {
        $this->Jira_Authorize();
        $report = jira_report::query()->with(['creator', 'project', 'epic'])->where('unique_id', $uniqueId)->firstOrFail();
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
        $report = jira_report::where('unique_id', $uniqueId)->firstOrFail();
        abort_unless($report->status === 'generated' && is_array($report->report_data), 409, 'El reporte aun no esta disponible.');
        return $this->PDF_GenerarPDF('pdf.jira_report', ['report' => $report, 'snapshot' => $report->data_snapshot ?? [], 'content' => $report->report_data ?? []]);
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
        $pdf = $this->Jira_DownloadReportPdf($request, $uniqueId);
        $relativePath = 'reports/jira/'.$report->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::disk('local')->put($relativePath, $pdf);
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
        );
        if (($mailResponse['status'] ?? 0) === 1) {
            $report->update(['last_emailed_at' => now()]);
        }

        return $mailResponse;
    }

    private function Jira_RunReportGeneration(jira_report $report, array $criteria): jira_report
    {
        $service = app(jira_report_service::class);
        try {
            $snapshot = $service->snapshot($criteria);
            $report->update(['status' => 'generating', 'data_snapshot' => $snapshot, 'report_data' => null, 'error_message' => null]);
            $response = $this->OpenIA_MakeQuestion(
                $service->prompt($criteria, $snapshot),
                null,
                ['purpose' => 'content', 'instructions' => $service->instructions($criteria['intention']), 'max_output_tokens' => 7000, 'json_schema' => $service->schema()],
            );
            if (($response['status'] ?? 0) !== 1) {
                throw new RuntimeException((string) ($response['message'] ?? 'No fue posible generar el reporte con IA.'));
            }
            $parsed = $service->parseResponse($response);
            if ($parsed === null) {
                throw new RuntimeException('La IA devolvio una respuesta que no se pudo interpretar.');
            }
            $report->update([
                'status' => 'generated',
                'report_data' => $service->normalize($parsed, $criteria),
                'ai_model' => $response['model'] ?? null,
                'ai_response_id' => $response['response_id'] ?? null,
                'generated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $message = Str::limit(trim($exception->getMessage()), 500, '');
            logger()->error('No fue posible generar un reporte Jira.', ['jira_report_id' => $report->id, 'message' => $message]);
            $report->update(['status' => 'failed', 'error_message' => $message]);
            throw ValidationException::withMessages(['report' => 'No fue posible generar el reporte Jira: '.Str::limit($message, 300, '')]);
        }

        return $report->fresh(['creator', 'project', 'epic']);
    }

    private function Jira_ValidateReportScope(array $criteria): void
    {
        if ($criteria['jira_epic_issue_id'] === null) {
            return;
        }
        $epic = jira_issue::findOrFail($criteria['jira_epic_issue_id']);
        if (! str_contains(strtolower((string) $epic->issue_type), 'epic')) {
            throw ValidationException::withMessages(['jira_epic_issue_id' => 'El issue seleccionado no es una epica.']);
        }
        if ($criteria['jira_project_id'] !== null && (int) $epic->jira_project_id !== (int) $criteria['jira_project_id']) {
            throw ValidationException::withMessages(['jira_epic_issue_id' => 'La epica no pertenece al proyecto seleccionado.']);
        }
    }

    private function Jira_ReportConnectionId(array $criteria): ?int
    {
        if ($criteria['jira_project_id'] === null) {
            return null;
        }
        return jira_project::find($criteria['jira_project_id'])?->jira_connection_id;
    }
}
