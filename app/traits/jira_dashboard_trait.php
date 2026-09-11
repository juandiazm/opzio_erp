<?php

namespace App\traits;

use App\Services\Jira\jira_metrics_service;
use App\Models\jira_issue;
use Illuminate\Http\Request;

trait jira_dashboard_trait
{
    public function Jira_DashboardData(Request $request): array
    {
        $this->Jira_Authorize();
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:jira_projects,id'],
            'epic_ids' => ['nullable', 'array'],
            'epic_ids.*' => ['integer', 'exists:jira_issues,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:jira_users,id'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', 'max:120'],
        ]);

        return app(jira_metrics_service::class)->dashboard($filters);
    }

    public function Jira_UpdateIssueHours(Request $request): array
    {
        $this->Jira_Authorize();
        $data = $request->validate([
            'jira_issue_id' => ['required', 'integer', 'exists:jira_issues,id'],
            'estimated_hours' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);
        $issue = jira_issue::findOrFail((int) $data['jira_issue_id']);
        $storyTypes = ['story', 'user story', 'historia', 'historia de usuario'];
        if (! in_array(strtolower(trim((string) $issue->issue_type)), $storyTypes, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['jira_issue_id' => 'Solo se pueden editar las horas de historias de usuario.']);
        }
        $issue->estimated_hours = round((float) $data['estimated_hours'], 2);
        $issue->estimated_hours_manual = true;
        $issue->save();

        return ['message' => 'Horas estimadas actualizadas correctamente.', 'estimated_hours' => (float) $issue->estimated_hours];
    }
}
