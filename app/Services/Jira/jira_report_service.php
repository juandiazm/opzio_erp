<?php

namespace App\Services\Jira;

use App\Models\jira_report;
use App\Models\jira_project;
use App\Models\jira_user;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class jira_report_service
{
    public static function intentions(): array
    {
        return [
            'internal_improvement' => 'Mejora interna',
            'client_report' => 'Informe para el cliente',
            'executive_summary' => 'Resumen ejecutivo',
            'team_capacity' => 'Capacidad y distribucion del equipo',
            'project_progress' => 'Avance por proyecto',
            'delivery_risks' => 'Riesgos de entrega',
            'unplanned_work' => 'Calidad y trabajo no planificado',
        ];
    }

    public static function dataSources(): array
    {
        return [
            'story_points' => 'Story Points',
            'worklogs' => 'Horas de worklog',
            'projects' => 'Proyectos',
            'epics' => 'Epicas',
            'users' => 'Usuarios',
            'statuses' => 'Estados',
            'erp_relations' => 'Relaciones ERP',
            'quality' => 'Calidad de datos',
        ];
    }

    public function __construct(private readonly jira_metrics_service $metrics)
    {
    }

    public function snapshot(array $criteria): array
    {
        $snapshot = $this->metrics->dashboard([
            'from' => $criteria['from_date'],
            'to' => $criteria['to_date'],
            'project_id' => $criteria['jira_project_id'] ?? null,
            'epic_id' => $criteria['jira_epic_issue_id'] ?? null,
        ]);
        $sources = $criteria['data_sources'];
        if (! in_array('story_points', $sources, true)) {
            $snapshot['summary']['story_points'] = null;
            $snapshot['projects'] = collect($snapshot['projects'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
            $snapshot['users'] = collect($snapshot['users'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
            $snapshot['epics'] = collect($snapshot['epics'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
        }
        if (! in_array('worklogs', $sources, true)) {
            $snapshot['summary']['worklog_hours'] = null;
            foreach (['projects', 'users', 'epics'] as $group) {
                $snapshot[$group] = collect($snapshot[$group])->map(fn (array $item): array => array_merge($item, ['hours' => null]))->all();
            }
        }
        if (! in_array('projects', $sources, true)) {
            $snapshot['projects'] = [];
        }
        if (! in_array('users', $sources, true)) {
            $snapshot['users'] = [];
        }
        if (! in_array('epics', $sources, true)) {
            $snapshot['epics'] = [];
        }
        if (! in_array('statuses', $sources, true)) {
            foreach ($snapshot['issues'] as &$issue) {
                unset($issue['resolved_at']);
            }
            unset($issue);
        }
        if (in_array('erp_relations', $sources, true)) {
            $projectRelations = jira_project::query()
                ->with(['clients', 'licenses'])
                ->when($criteria['jira_project_id'] ?? null, fn ($query, $projectId) => $query->whereKey($projectId))
                ->orderBy('name')
                ->get()
                ->map(fn (jira_project $project): array => [
                    'project_key' => $project->project_key,
                    'project_name' => $project->name,
                    'clients' => $project->clients->map(fn ($client): array => [
                        'id' => $client->id,
                        'name' => $client->complete_name,
                    ])->values()->all(),
                    'licenses' => $project->licenses->map(fn ($license): array => [
                        'id' => $license->id,
                        'name' => $license->name,
                    ])->values()->all(),
                ])->values()->all();
            $snapshot['erp_relations'] = ['included' => true, 'projects' => $projectRelations];
        } else {
            $snapshot['erp_relations'] = ['included' => false];
        }
        if (in_array('quality', $sources, true)) {
            $issues = collect($snapshot['issues'] ?? []);
            $snapshot['quality'] = [
                'included' => true,
                'issues_without_epic' => $issues->where('epic', 'Sin epica')->count(),
                'issues_without_assignee' => $issues->where('assignee', 'Sin responsable')->count(),
                'users_without_mapping' => jira_user::query()->whereDoesntHave('mapping')->count(),
            ];
        } else {
            $snapshot['quality'] = ['included' => false];
        }
        $snapshot['report'] = [
            'intention' => $criteria['intention'],
            'intention_label' => self::intentions()[$criteria['intention']] ?? $criteria['intention'],
            'sources' => $sources,
            'context' => $criteria['context_prompt'] ?? null,
            'generated_from_sync' => now()->toIso8601String(),
        ];

        return $snapshot;
    }

    public function prompt(array $criteria, array $snapshot): string
    {
        return 'Genera el reporte Jira "'.($criteria['title'] ?: 'Reporte Jira').'" para el periodo '.$criteria['from_date'].' a '.$criteria['to_date'].".\n"
            .'Intencion: '.(self::intentions()[$criteria['intention']] ?? $criteria['intention'])."\n"
            .'Usa exclusivamente las fuentes seleccionadas: '.implode(', ', array_map(fn (string $source): string => self::dataSources()[$source] ?? $source, $criteria['data_sources'])).".\n"
            .'Contexto adicional: '.($criteria['context_prompt'] ?: 'Sin contexto adicional.')."\n"
            .'Story Points son estimaciones y no equivalen a horas. No inventes datos, causas, porcentajes ni resultados. Distingue hechos de hipotesis.\n'
            .'Datos consolidados en JSON:\n'.json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function instructions(string $intention): string
    {
        return 'Actua como analista senior de proyectos y capacidad de equipos. Escribe en espanol claro, preciso y accionable. '
            .'La intencion del reporte es: '.(self::intentions()[$intention] ?? $intention).'. '
            .'Usa exclusivamente el JSON recibido, diferencia Story Points de horas y senala limitaciones cuando falten datos. '
            .'Responde exclusivamente con un objeto JSON que cumpla el esquema solicitado.';
    }

    public function schema(): array
    {
        return [
            'name' => 'jira_work_report',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'report_title' => ['type' => 'string'],
                    'executive_summary' => ['type' => 'string'],
                    'objective_alignment' => ['type' => 'string'],
                    'key_findings' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'effort_analysis' => ['type' => 'string'],
                    'project_analysis' => ['type' => 'string'],
                    'user_analysis' => ['type' => 'string'],
                    'epic_analysis' => ['type' => 'string'],
                    'risks' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'recommendations' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'priority' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']], 'rationale' => ['type' => 'string'], 'action' => ['type' => 'string']], 'required' => ['title', 'priority', 'rationale', 'action'], 'additionalProperties' => false]],
                    'next_steps' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'limitations' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'required' => ['report_title', 'executive_summary', 'objective_alignment', 'key_findings', 'effort_analysis', 'project_analysis', 'user_analysis', 'epic_analysis', 'risks', 'recommendations', 'next_steps', 'limitations'],
                'additionalProperties' => false,
            ],
        ];
    }

    public function normalize(array $data, array $criteria): array
    {
        $strings = fn (mixed $items): array => collect(is_array($items) ? $items : [])->map(fn ($item): string => trim((string) $item))->filter()->values()->all();
        $recommendations = collect(is_array($data['recommendations'] ?? null) ? $data['recommendations'] : [])->filter('is_array')->map(fn (array $item): array => [
            'title' => trim((string) ($item['title'] ?? 'Recomendacion')) ?: 'Recomendacion',
            'priority' => in_array($item['priority'] ?? null, ['alta', 'media', 'baja'], true) ? $item['priority'] : 'media',
            'rationale' => trim((string) ($item['rationale'] ?? '')),
            'action' => trim((string) ($item['action'] ?? '')),
        ])->values()->all();

        return [
            'report_title' => trim((string) ($data['report_title'] ?? $criteria['title'])) ?: $criteria['title'],
            'executive_summary' => trim((string) ($data['executive_summary'] ?? 'No se genero un resumen.')),
            'objective_alignment' => trim((string) ($data['objective_alignment'] ?? '')),
            'key_findings' => $strings($data['key_findings'] ?? []),
            'effort_analysis' => trim((string) ($data['effort_analysis'] ?? '')),
            'project_analysis' => trim((string) ($data['project_analysis'] ?? '')),
            'user_analysis' => trim((string) ($data['user_analysis'] ?? '')),
            'epic_analysis' => trim((string) ($data['epic_analysis'] ?? '')),
            'risks' => $strings($data['risks'] ?? []),
            'recommendations' => $recommendations,
            'next_steps' => $strings($data['next_steps'] ?? []),
            'limitations' => $strings($data['limitations'] ?? []),
        ];
    }

    public function validateCriteria(array $data): array
    {
        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->startOfDay();
        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages(['to_date' => 'La fecha final debe ser igual o posterior a la inicial.']);
        }
        if ($from->diffInDays($to) > 365) {
            throw ValidationException::withMessages(['to_date' => 'El rango maximo para un reporte es de 366 dias.']);
        }
        $sources = array_values(array_unique(array_filter($data['data_sources'] ?? [])));
        if ($sources === []) {
            throw ValidationException::withMessages(['data_sources' => 'Selecciona al menos una fuente de datos.']);
        }

        return [
            'title' => trim((string) ($data['title'] ?? '')) ?: 'Reporte Jira '.$from->format('d/m/Y').' - '.$to->format('d/m/Y'),
            'intention' => (string) $data['intention'],
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'jira_project_id' => filled($data['jira_project_id'] ?? null) ? (int) $data['jira_project_id'] : null,
            'jira_epic_issue_id' => filled($data['jira_epic_issue_id'] ?? null) ? (int) $data['jira_epic_issue_id'] : null,
            'data_sources' => $sources,
            'context_prompt' => filled($data['context_prompt'] ?? null) ? trim((string) $data['context_prompt']) : null,
        ];
    }

    public function parseResponse(array $response): ?array
    {
        foreach (($response['data'] ?? []) as $value) {
            if (is_array($value)) {
                return $value;
            }
            $text = trim((string) $value);
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            $withoutFence = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
            $decoded = json_decode(trim((string) $withoutFence), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
