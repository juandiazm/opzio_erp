<?php

namespace App\traits;

use App\Models\client;
use App\Models\employee;
use App\Models\jira_issue;
use App\Models\jira_project;
use App\Models\jira_user;
use App\Models\jira_user_mapping;
use App\Models\license;
use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait jira_relations_trait
{
    public function Jira_RelationsData(Request $request): array
    {
        $this->Jira_Authorize();
        $epicLicenseIds = DB::table('jira_epic_licenses')->pluck('license_id', 'jira_issue_id');

        return [
            'projects' => jira_project::query()->with(['clients:id,name,lastname', 'licenses:id,name,client_id'])->orderBy('name')->get()->map(fn (jira_project $project): array => [
                'id' => $project->id,
                'project_key' => $project->project_key,
                'name' => $project->name,
                'client_ids' => $project->clients->pluck('id')->values()->all(),
                'license_ids' => $project->licenses->pluck('id')->values()->all(),
                'clients' => $project->clients->map(fn (client $item): array => ['id' => $item->id, 'name' => $item->complete_name])->values()->all(),
                'licenses' => $project->licenses->map(fn (license $item): array => ['id' => $item->id, 'name' => $item->name])->values()->all(),
            ])->values()->all(),
            'clients' => client::query()->where('active', 1)->orderBy('name')->get(['id', 'name', 'lastname'])->map(fn (client $item): array => ['id' => $item->id, 'name' => $item->complete_name])->values()->all(),
            'licenses' => license::query()->with('client:id,name,lastname')->orderBy('name')->get(['id', 'name', 'client_id'])->map(fn (license $item): array => ['id' => $item->id, 'name' => $item->name, 'client_id' => $item->client_id, 'client_name' => $item->client?->complete_name])->values()->all(),
            'epics' => jira_issue::query()->where('issue_type', 'like', '%Epic%')->orderBy('summary')->get(['id', 'issue_key', 'summary', 'jira_project_id'])->map(fn (jira_issue $item): array => [
                'id' => $item->id,
                'issue_key' => $item->issue_key,
                'summary' => $item->summary,
                'jira_project_id' => $item->jira_project_id,
                'license_id' => $epicLicenseIds[$item->id] ?? null,
            ])->values()->all(),
            'users' => jira_user::query()->with('mapping')->orderBy('display_name')->get()->map(fn (jira_user $item): array => [
                'id' => $item->id,
                'account_id' => $item->account_id,
                'display_name' => $item->display_name,
                'mapping' => $item->mapping ? ['user_id' => $item->mapping->user_id, 'employee_id' => $item->mapping->employee_id] : null,
            ])->values()->all(),
            'erp_users' => user::query()->orderBy('name')->get(['id', 'name', 'lastname'])->map(fn (user $item): array => ['id' => $item->id, 'name' => $item->complete_name])->values()->all(),
            'employees' => employee::query()->orderBy('name')->get(['id', 'name', 'last_name'])->map(fn (employee $item): array => ['id' => $item->id, 'name' => $item->complete_name])->values()->all(),
        ];
    }

    public function Jira_SaveProjectRelations(Request $request): array
    {
        $this->Jira_Authorize();
        $data = $request->validate([
            'jira_project_id' => ['required', 'integer', 'exists:jira_projects,id'],
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => ['integer', 'exists:clients,id'],
            'license_ids' => ['nullable', 'array'],
            'license_ids.*' => ['integer', 'exists:licenses,id'],
        ]);
        $project = jira_project::findOrFail((int) $data['jira_project_id']);
        $clientIds = collect($data['client_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        $licenseIds = collect($data['license_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        $invalidLicense = license::query()->whereIn('id', $licenseIds)->whereNotIn('client_id', $clientIds)->exists();
        if ($invalidLicense) {
            throw ValidationException::withMessages(['license_ids' => 'Cada licencia debe pertenecer a un cliente asociado al proyecto.']);
        }
        DB::transaction(function () use ($project, $clientIds, $licenseIds): void {
            $project->clients()->sync($clientIds->mapWithKeys(fn (int $id, int $index): array => [$id => ['is_primary' => $index === 0]])->all());
            $project->licenses()->sync($licenseIds->all());
        });

        return ['message' => 'Relaciones de proyecto guardadas correctamente.'];
    }

    public function Jira_SaveUserMapping(Request $request): array
    {
        $this->Jira_Authorize();
        $data = $request->validate([
            'jira_user_id' => ['nullable', 'integer', 'exists:jira_users,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_id' => ['required_without:user_id', 'nullable', 'integer', 'exists:employees,id'],
        ]);
        if (blank($data['jira_user_id'] ?? null) && filled($data['employee_id'] ?? null)) {
            jira_user_mapping::where('employee_id', (int) $data['employee_id'])->delete();

            return ['message' => 'Asignación de empleado eliminada correctamente.'];
        }
        $hasUser = filled($data['user_id'] ?? null);
        $hasEmployee = filled($data['employee_id'] ?? null);
        if ($hasUser === $hasEmployee) {
            throw ValidationException::withMessages(['mapping' => 'Asocia el usuario Jira con un usuario ERP o con un empleado, pero no con ambos.']);
        }
        if ($hasEmployee) {
            jira_user_mapping::where('employee_id', (int) $data['employee_id'])
                ->where('jira_user_id', '!=', (int) $data['jira_user_id'])
                ->delete();
        }
        jira_user_mapping::updateOrCreate(
            ['jira_user_id' => (int) $data['jira_user_id']],
            ['user_id' => $hasUser ? (int) $data['user_id'] : null, 'employee_id' => $hasEmployee ? (int) $data['employee_id'] : null, 'mapping_source' => 'manual'],
        );

        return ['message' => 'Mapping de usuario guardado correctamente.'];
    }

    public function Jira_SaveEpicLicense(Request $request): array
    {
        $this->Jira_Authorize();
        $data = $request->validate([
            'jira_issue_id' => ['required', 'integer', 'exists:jira_issues,id'],
            'license_id' => ['nullable', 'integer', 'exists:licenses,id'],
        ]);
        $issue = jira_issue::with('project')->findOrFail((int) $data['jira_issue_id']);
        if (! str_contains(strtolower((string) $issue->issue_type), 'epic')) {
            throw ValidationException::withMessages(['jira_issue_id' => 'Solo se pueden relacionar issues de tipo Epic.']);
        }
        if (blank($data['license_id'] ?? null)) {
            DB::table('jira_epic_licenses')->where('jira_issue_id', $issue->id)->delete();
        } else {
            $license = license::findOrFail((int) $data['license_id']);
            if (! $issue->project->licenses()->whereKey($license->id)->exists()) {
                throw ValidationException::withMessages(['license_id' => 'La licencia debe estar asociada al proyecto Jira.']);
            }
            DB::table('jira_epic_licenses')->updateOrInsert(['jira_issue_id' => $issue->id], ['license_id' => $license->id, 'updated_at' => now(), 'created_at' => now()]);
        }

        return ['message' => 'Relacion de epica guardada correctamente.'];
    }
}
