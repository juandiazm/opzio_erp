<?php

namespace App\Http\Controllers;

use App\Models\jira_connection;
use App\Models\jira_issue;
use App\Models\jira_project;
use App\Models\jira_report;
use App\Models\jira_user;
use App\traits\jira_configuration_trait;
use App\traits\jira_dashboard_trait;
use App\traits\jira_relations_trait;
use App\traits\jira_reports_trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class jira_controller extends Controller
{
    use jira_configuration_trait;
    use jira_relations_trait;
    use jira_dashboard_trait;
    use jira_reports_trait;

    public function page(Request $request): View
    {
        $this->Jira_Authorize();

        return view('erp.jira', [
            'connection' => jira_connection::query()->latest('updated_at')->first(),
            'syncedStories' => jira_issue::query()
                ->whereRaw('LOWER(TRIM(issue_type)) IN (?, ?, ?, ?)', [
                    'story',
                    'user story',
                    'historia',
                    'historia de usuario',
                ])
                ->count(),
            'projects' => jira_project::query()->with(['clients', 'licenses'])->orderBy('name')->get(),
            'jiraUsers' => jira_user::query()->with('mapping')->orderBy('display_name')->get(),
            'jiraStatuses' => jira_issue::query()
                ->whereRaw('LOWER(TRIM(issue_type)) IN (?, ?, ?, ?)', ['story', 'user story', 'historia', 'historia de usuario'])
                ->whereNotNull('status')
                ->where('status', '<>', '')
                ->distinct()
                ->orderBy('status')
                ->pluck('status'),
            'reports' => jira_report::query()->with(['creator', 'project', 'epic'])->latest('updated_at')->limit(25)->get(),
        ]);
    }

    public function save_connection(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_SaveConnection($request));
    }

    public function test_connection(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_TestConnection($request));
    }

    public function sync_connection(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_SyncConnection($request));
    }

    public function relations_data(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_RelationsData($request));
    }

    public function productivity_data(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_ProductivityData($request));
    }

    public function save_project_relations(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_SaveProjectRelations($request));
    }

    public function save_project_productivity(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_SaveProjectProductivity($request));
    }

    public function save_user_mapping(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_SaveUserMapping($request));
    }

    public function save_epic_license(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_SaveEpicLicense($request));
    }

    public function dashboard_data(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_DashboardData($request));
    }

    public function update_issue_hours(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_UpdateIssueHours($request));
    }

    public function reports_data(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_ReportsData($request));
    }

    public function generate_report(Request $request): JsonResponse
    {
        return $this->jiraJson(fn (): array => ['report' => $this->Jira_GenerateReport($request)]);
    }

    public function report_detail(Request $request, string $uniqueId): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_ReportDetail($request, $uniqueId));
    }

    public function regenerate_report(Request $request, string $uniqueId): JsonResponse
    {
        return $this->jiraJson(fn (): array => ['report' => $this->Jira_RegenerateReport($request, $uniqueId)]);
    }

    public function delete_report(Request $request, string $uniqueId): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_DeleteReport($request, $uniqueId));
    }

    public function restore_report(Request $request, string $uniqueId): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_RestoreReport($request, $uniqueId));
    }

    public function download_report_pdf(Request $request, string $uniqueId): Response
    {
        $pdf = $this->Jira_DownloadReportPdf($request, $uniqueId);
        $report = jira_report::where('unique_id', $uniqueId)->firstOrFail();
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="jira-'.str($report->title)->slug().'.pdf"',
        ]);
    }

    public function email_report(Request $request, string $uniqueId): JsonResponse
    {
        return $this->jiraJson(fn (): array => $this->Jira_EmailReport($request, $uniqueId));
    }

    private function jiraJson(callable $callback): JsonResponse
    {
        try {
            $data = $callback();
            if (($data['ok'] ?? true) === false) {
                return response()->json([
                    'status' => 0,
                    'message' => $data['message'] ?? 'La operacion Jira no fue exitosa.',
                    'data' => $data,
                ], 422);
            }

            return response()->json(['status' => 1, 'data' => $data]);
        } catch (ValidationException $exception) {
            return response()->json(['status' => 0, 'message' => 'La informacion enviada no es valida.', 'errors' => $exception->errors()], 422);
        } catch (Throwable $exception) {
            logger()->error('Jira ERP request failed.', ['message' => $exception->getMessage()]);
            return response()->json(['status' => 0, 'message' => $exception->getMessage()], 422);
        }
    }
}
