<?php

namespace App\traits;

use App\Services\Jira\jira_metrics_service;
use Illuminate\Http\Request;

trait jira_dashboard_trait
{
    public function Jira_DashboardData(Request $request): array
    {
        $this->Jira_Authorize();
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:jira_projects,id'],
            'epic_id' => ['nullable', 'integer', 'exists:jira_issues,id'],
            'user_id' => ['nullable', 'integer', 'exists:jira_users,id'],
        ]);

        return app(jira_metrics_service::class)->dashboard($filters);
    }
}
