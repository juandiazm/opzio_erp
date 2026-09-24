<?php

namespace App\Services\Jira;

use App\Models\jira_issue;
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
            'client_report' => 'Resultados para cliente',
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
        $criteria = $this->normalizeFilterCriteria($criteria);
        $snapshot = $this->metrics->dashboard([
            'from' => $criteria['from_date'],
            'to' => $criteria['to_date'],
            'project_ids' => $criteria['project_ids'],
            'epic_ids' => $criteria['epic_ids'],
            'user_ids' => $criteria['user_ids'],
            'statuses' => $criteria['statuses'],
            'include_all_issue_types' => $criteria['intention'] === 'client_report',
            'completed_only' => $criteria['intention'] !== 'client_report',
        ]);
        $sources = $criteria['data_sources'];
        if (! in_array('story_points', $sources, true)) {
            $snapshot['summary']['story_points'] = null;
            $snapshot['projects'] = collect($snapshot['projects'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
            $snapshot['users'] = collect($snapshot['users'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
            $snapshot['epics'] = collect($snapshot['epics'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
            $snapshot['issues'] = collect($snapshot['issues'])->map(fn (array $item): array => array_merge($item, ['story_points' => null]))->all();
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
                ->when($criteria['project_ids'] !== [], fn ($query) => $query->whereIn('id', $criteria['project_ids']))
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
        if ($criteria['intention'] === 'client_report') {
            $snapshot['client_report'] = $this->clientReportSource($snapshot);
        }
        $snapshot['report'] = [
            'intention' => $criteria['intention'],
            'intention_label' => self::intentions()[$criteria['intention']] ?? $criteria['intention'],
            'sources' => $sources,
            'context' => $criteria['context_prompt'] ?? null,
            'filters' => $this->filterLabels($criteria),
            'generated_from_sync' => now()->toIso8601String(),
        ];

        return $snapshot;
    }

    public function prompt(array $criteria, array $snapshot): string
    {
        if (($criteria['intention'] ?? null) === 'client_report') {
            return $this->clientReportPrompt($criteria, $snapshot);
        }

        return 'Genera un informe de resultados "'.($criteria['title'] ?: 'Informe de resultados').'" para el periodo '.$criteria['from_date'].' a '.$criteria['to_date'].".\n"
            .'Usa exclusivamente las fuentes seleccionadas: '.implode(', ', array_map(fn (string $source): string => self::dataSources()[$source] ?? $source, $criteria['data_sources'])).".\n"
            .'Filtros aplicados del dashboard: '.json_encode($snapshot['report']['filters'] ?? $snapshot['filters'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
            .'Contexto adicional: '.($criteria['context_prompt'] ?: 'Sin contexto adicional.')."\n"
            .'Story Points son estimaciones y no equivalen a horas. No inventes datos, causas, porcentajes ni resultados. Distingue hechos de hipotesis.\n'
            .'Datos consolidados en JSON:\n'.json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function instructions(string $intention): string
    {
        if ($intention === 'client_report') {
            return 'Actua como analista senior y redactor ejecutivo de resultados para un cliente. Escribe en espanol claro, preciso y accionable. No incluyas etiquetas tecnicas, nombres de herramientas ni la frase "Informe para el cliente" en el contenido visible. Usa exclusivamente el JSON recibido y distingue hechos de hipotesis. Responde exclusivamente con el objeto JSON solicitado.';
        }

        return 'Actua como analista senior de proyectos y capacidad de equipos. Escribe en espanol claro, preciso y accionable. '
            .'La intencion del reporte es: '.(self::intentions()[$intention] ?? $intention).'. '
            .'Usa exclusivamente el JSON recibido, diferencia Story Points de horas y senala limitaciones cuando falten datos. '
            .'Responde exclusivamente con un objeto JSON que cumpla el esquema solicitado.';
    }

    public function schema(?string $intention = null): array
    {
        if ($intention === 'client_report') {
            return $this->clientReportSchema();
        }

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

    public function normalize(array $data, array $criteria, ?array $snapshot = null): array
    {
        if (($criteria['intention'] ?? null) === 'client_report') {
            return $this->normalizeClientReport($data, $criteria, $snapshot ?? []);
        }

        $strings = fn (mixed $items): array => collect(is_array($items) ? $items : [])->map(fn ($item): string => trim((string) $item))->filter()->values()->all();
        $recommendations = collect(is_array($data['recommendations'] ?? null) ? $data['recommendations'] : [])->filter(fn ($item): bool => is_array($item))->map(fn (array $item): array => [
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

    private function clientReportSchema(): array
    {
        return [
            'name' => 'jira_client_report',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'report_title' => ['type' => 'string'],
                    'executive_summary' => ['type' => 'string'],
                    'documented_results' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'paragraphs' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            'required' => ['title', 'paragraphs'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'effort_analysis' => ['type' => 'string'],
                    'interpretation_notes' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'closing' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'activity_summaries' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'issue_key' => ['type' => 'string'],
                                'result_summary' => ['type' => 'string'],
                            ],
                            'required' => ['issue_key', 'result_summary'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['report_title', 'executive_summary', 'documented_results', 'effort_analysis', 'interpretation_notes', 'closing', 'activity_summaries'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function clientReportPrompt(array $criteria, array $snapshot): string
    {
        $clientData = [
            'period' => ['from' => $criteria['from_date'], 'to' => $criteria['to_date']],
            'purpose' => 'Informe ejecutivo de resultados',
            'filters' => data_get($snapshot, 'report.filters', []),
            'effort' => data_get($snapshot, 'client_report', []),
            'context' => $criteria['context_prompt'] ?? null,
        ];

        return $this->clientReportGuidelines()
            ."\nGenera un informe de resultados para un cliente a partir exclusivamente de los datos JSON suministrados. "
            .'El titulo solicitado es: '.($criteria['title'] ?: 'Informe de resultados')."\n"
            .'La respuesta debe cumplir exactamente el esquema JSON solicitado y no debe contener HTML, Markdown ni tablas.\n'
            .'La estructura narrativa obligatoria es: Resumen ejecutivo; Resultados documentados, agrupados en lineas de trabajo; Esfuerzo registrado; Consideraciones de interpretacion; Cierre.\n'
            .'En Resultados documentados distingue hechos confirmados de necesidades, propuestas, criterios o pendientes. No crees una seccion separada para actividades sin resultado confirmado.\n'
            .'Incluye una sintesis breve para cada actividad de effort.activities usando su issue_key. Si no hay evidencia de resultado, escribe una formulacion neutral como "La actividad contemplo..." y no la presentes como terminada.\n'
            .'No uses las palabras "Jira" ni "Informe para el cliente" en los titulos o parrafos de salida.\n'
            .'Usa exactamente los valores calculados en effort: no redondees ni recalcules el esfuerzo. No menciones Story Points ni horas.\n'
            .'Datos de trabajo:\n'.json_encode($clientData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function clientReportGuidelines(): string
    {
        return <<<'TEXT'
Redaccion para un informe de resultados dirigido a un cliente:
- Escribe en espanol formal, claro y natural, con ortografia y puntuacion cuidadas.
- Usa un tono ejecutivo, sobrio y orientado a resultados; evita lenguaje de Jira, lenguaje de desarrollo y expresiones internas.
- Presenta unicamente hechos respaldados por la descripcion o los comentarios. Usa verbos como "se implemento", "se corrigio" o "se valido" solo cuando la fuente lo confirme de forma explicita.
- Si la fuente solo describe una necesidad, solicitud o alcance, usa "se documento", "la actividad contemplo" o "se registro".
- No inventes beneficios, ahorros, fechas, publicaciones, validaciones ni resultados comerciales.
- Habla únicamente de Esfuerzo. El Esfuerzo corresponde al valor de estimated_hours registrado para cada actividad.
- No menciones Story Points, puntos de historia, horas, worklogs ni equivalencias de tiempo.
- Mantén una redaccion consistente en pasado o presente perfecto cuando describas actividades confirmadas. Evita frases telegráficas y verbos en infinitivo como instrucciones pendientes.
- No uses las palabras "issue", "backlog", "quick win", "Definition of Done", "hecho cuando", "trabajo a realizar", "objetivo", "alcance" ni "tiempo invertido".
- No uses "N/A" ni abreviaturas tecnicas sin explicar su significado.
- No muestres Markdown, bloques de codigo ni encabezados en ingles.
TEXT;
    }

    private function clientReportSource(array $snapshot): array
    {
        $summary = is_array($snapshot['summary'] ?? null) ? $snapshot['summary'] : [];
        $activities = collect($snapshot['issues'] ?? [])->map(fn (array $issue): array => [
            'issue_key' => (string) ($issue['key'] ?? ''),
            'summary' => trim((string) ($issue['summary'] ?? 'Sin titulo')),
            'issue_type' => $this->clientIssueTypeLabel($issue['issue_type'] ?? null),
            'priority' => $this->clientPriorityLabel($issue['priority'] ?? null),
            'parent_summary' => trim((string) ($issue['parent'] ?? $issue['epic'] ?? 'Sin padre')) ?: 'Sin padre',
            'effort' => $issue['estimated_hours'] ?? null,
            'description' => $this->clientSourceText($issue['description'] ?? null, 3500),
            'comments' => collect(is_array($issue['comments'] ?? null) ? $issue['comments'] : [])
                ->map(fn (array $comment): array => [
                    'author' => trim((string) ($comment['author'] ?? '')),
                    'created' => $comment['created'] ?? null,
                    'content' => $this->clientSourceText($comment['content'] ?? null, 2200),
                ])
                ->filter(fn (array $comment): bool => $comment['content'] !== '')
                ->values()
                ->all(),
        ])->values()->all();

        $effortActivities = collect($activities)->filter(fn (array $activity): bool => is_numeric($activity['effort'] ?? null));

        return [
            'total_records' => (int) ($summary['issue_count'] ?? count($activities)),
            'total_effort' => round((float) $effortActivities->sum(fn (array $activity): float => (float) $activity['effort']), 2),
            'records_by_type' => collect($summary['issue_type_counts'] ?? [])->mapWithKeys(fn ($count, $type): array => [$this->clientIssueTypeLabel($type) => (int) $count])->all(),
            'effort_by_type' => $effortActivities->groupBy('issue_type')->map(fn ($items): float => round($items->sum(fn (array $activity): float => (float) $activity['effort']), 2))->all(),
            'unestimated_records' => count($activities) - $effortActivities->count(),
            'activities' => $activities,
        ];
    }

    private function normalizeClientReport(array $data, array $criteria, array $snapshot): array
    {
        $strings = fn (mixed $items): array => collect(is_array($items) ? $items : [])->map(fn ($item): string => $this->clientCleanText($item))->filter()->values()->all();
        $sourceActivities = collect(data_get($snapshot, 'client_report.activities', []));
        $generatedActivities = collect(is_array($data['activity_summaries'] ?? null) ? $data['activity_summaries'] : [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['issue_key'] ?? null))
            ->keyBy(fn (array $item): string => trim((string) $item['issue_key']));
        $activities = $sourceActivities->map(function (array $source) use ($generatedActivities): array {
            $key = trim((string) ($source['issue_key'] ?? ''));
            $generated = $generatedActivities->get($key, []);
            $fallback = 'La actividad contemplo '.$this->clientSourceSentence($source['summary'] ?? 'la actividad registrada').'. No se documentaron resultados adicionales.';

            return [
                'issue_key' => $key,
                'summary' => $source['summary'] ?? 'Sin titulo',
                'issue_type' => $source['issue_type'] ?? 'Sin clasificar',
                'priority' => $source['priority'] ?? 'No registrada',
                'parent_summary' => $source['parent_summary'] ?? 'Sin padre',
                'effort' => $source['effort'] ?? null,
                'result_summary' => $this->clientCleanText(trim((string) ($generated['result_summary'] ?? '')) ?: $fallback),
            ];
        })->values()->all();

        $documentedResults = collect(is_array($data['documented_results'] ?? null) ? $data['documented_results'] : [])
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'title' => $this->clientCleanText($item['title'] ?? 'Resultados documentados') ?: 'Resultados documentados',
                'paragraphs' => $strings($item['paragraphs'] ?? []),
            ])
            ->filter(fn (array $item): bool => $item['paragraphs'] !== [])
            ->values()
            ->all();
        $interpretationNotes = $strings($data['interpretation_notes'] ?? []);
        if ($interpretationNotes === []) {
            $interpretationNotes[] = 'El Esfuerzo corresponde al valor de estimated_hours registrado para las actividades seleccionadas.';
            $interpretationNotes[] = 'Las actividades sin estimated_hours se muestran como sin esfuerzo registrado.';
        }

        return [
            'format' => 'client_report',
            'report_title' => $this->clientCleanText($data['report_title'] ?? $criteria['title']) ?: $criteria['title'],
            'executive_summary' => $this->clientCleanText($data['executive_summary'] ?? 'No se genero un resumen.'),
            'documented_results' => $documentedResults,
            'effort_analysis' => $this->clientCleanText($data['effort_analysis'] ?? ''),
            'interpretation_notes' => $interpretationNotes,
            'closing' => $strings($data['closing'] ?? []),
            'activity_summaries' => $activities,
        ];
    }

    private function clientIssueTypeLabel(mixed $value): string
    {
        return match (strtolower(trim((string) $value))) {
            'story', 'user story', 'historia', 'historia de usuario' => 'Historia',
            'bug', 'error', 'incidencia' => 'Incidencia',
            'task', 'tarea' => 'Tarea',
            'epic', 'epica' => 'Epica',
            default => filled($value) ? trim((string) $value) : 'Sin clasificar',
        };
    }

    private function clientPriorityLabel(mixed $value): string
    {
        return match (strtolower(trim((string) $value))) {
            'highest', 'muy alta' => 'Muy alta',
            'high', 'alta' => 'Alta',
            'medium', 'media' => 'Media',
            'low', 'baja' => 'Baja',
            'lowest', 'muy baja' => 'Muy baja',
            default => filled($value) ? trim((string) $value) : 'No registrada',
        };
    }

    private function clientSourceText(mixed $value, int $limit): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\b(password|contrasena|contraseña|api[_ -]?token|secret|clave)\s*[:=]\s*[^\s,;]+/iu', '$1: [omitido]', $text) ?? $text;
        return Str::limit(trim($text), $limit, '...');
    }

    private function clientSourceSentence(string $summary): string
    {
        $summary = trim($summary);
        return $summary === '' ? 'la actividad registrada' : '«'.Str::limit($summary, 180, '...').'»';
    }

    private function clientCleanText(mixed $value): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\b(?:story\s+points|puntos\s+de\s+historia)\b/iu', 'Esfuerzo', $text) ?? $text;
        $text = preg_replace('/\bhoras?(?:\s+(?:trabajadas?|registradas?|estimadas?|de\s+trabajo|de\s+worklog))?\b/iu', 'Esfuerzo', $text) ?? $text;
        $text = preg_replace('/\bworklogs?\b/iu', 'registros de esfuerzo', $text) ?? $text;

        return trim($text);
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

        $projectIds = $this->normalizeIds($data['project_ids'] ?? ($data['jira_project_ids'] ?? ($data['jira_project_id'] ?? null)));
        $epicIds = $this->normalizeIds($data['epic_ids'] ?? ($data['jira_epic_issue_ids'] ?? ($data['jira_epic_issue_id'] ?? null)));
        $userIds = $this->normalizeIds($data['user_ids'] ?? ($data['jira_user_ids'] ?? null));
        $statuses = $this->normalizeStatuses($data['statuses'] ?? ($data['jira_statuses'] ?? null));

        return [
            'title' => trim((string) ($data['title'] ?? '')) ?: 'Informe de resultados '.$from->format('d/m/Y').' - '.$to->format('d/m/Y'),
            'intention' => (string) $data['intention'],
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'project_ids' => $projectIds,
            'epic_ids' => $epicIds,
            'user_ids' => $userIds,
            'statuses' => $statuses,
            'jira_project_id' => $projectIds[0] ?? null,
            'jira_epic_issue_id' => $epicIds[0] ?? null,
            'data_sources' => $sources,
            'context_prompt' => filled($data['context_prompt'] ?? null) ? trim((string) $data['context_prompt']) : null,
        ];
    }

    private function normalizeFilterCriteria(array $criteria): array
    {
        $criteria['project_ids'] = $this->normalizeIds($criteria['project_ids'] ?? ($criteria['jira_project_ids'] ?? ($criteria['jira_project_id'] ?? null)));
        $criteria['epic_ids'] = $this->normalizeIds($criteria['epic_ids'] ?? ($criteria['jira_epic_issue_ids'] ?? ($criteria['jira_epic_issue_id'] ?? null)));
        $criteria['user_ids'] = $this->normalizeIds($criteria['user_ids'] ?? ($criteria['jira_user_ids'] ?? null));
        $criteria['statuses'] = $this->normalizeStatuses($criteria['statuses'] ?? ($criteria['jira_statuses'] ?? null));

        return $criteria;
    }

    private function filterLabels(array $criteria): array
    {
        $projectIds = $criteria['project_ids'];
        $epicIds = $criteria['epic_ids'];
        $userIds = $criteria['user_ids'];

        return [
            'project_ids' => $projectIds,
            'projects' => jira_project::query()
                ->whereIn('id', $projectIds)
                ->orderBy('name')
                ->get(['id', 'project_key', 'name'])
                ->map(fn (jira_project $project): array => [
                    'id' => $project->id,
                    'label' => $project->project_key.' - '.$project->name,
                ])
                ->values()
                ->all(),
            'epic_ids' => $epicIds,
            'epics' => jira_issue::query()
                ->whereIn('id', $epicIds)
                ->orderBy('summary')
                ->get(['id', 'issue_key', 'summary'])
                ->map(fn (jira_issue $epic): array => [
                    'id' => $epic->id,
                    'label' => $epic->issue_key.' - '.$epic->summary,
                ])
                ->values()
                ->all(),
            'user_ids' => $userIds,
            'users' => jira_user::query()
                ->whereIn('id', $userIds)
                ->orderBy('display_name')
                ->get(['id', 'display_name'])
                ->map(fn (jira_user $user): array => [
                    'id' => $user->id,
                    'label' => $user->display_name,
                ])
                ->values()
                ->all(),
            'statuses' => $criteria['statuses'],
        ];
    }

    private function normalizeIds(mixed $value): array
    {
        return collect(is_array($value) ? $value : (filled($value) ? [$value] : []))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeStatuses(mixed $value): array
    {
        return collect(is_array($value) ? $value : (filled($value) ? [$value] : []))
            ->map(fn ($status): string => trim((string) $status))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function parseResponse(array $response): ?array
    {
        $candidates = [];
        foreach (['data', 'output_text', 'output'] as $key) {
            if (array_key_exists($key, $response)) {
                $candidates[] = $response[$key];
            }
        }

        foreach ($candidates as $candidate) {
            $parsed = $this->parseResponseCandidate($candidate);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseResponseCandidate(mixed $candidate): ?array
    {
        if (is_array($candidate)) {
            if ($candidate === []) {
                return null;
            }
            if (! array_is_list($candidate)) {
                foreach (['output_text', 'text', 'value', 'content', 'data'] as $key) {
                    if (array_key_exists($key, $candidate)) {
                        $parsed = $this->parseResponseCandidate($candidate[$key]);
                        if ($parsed !== null) {
                            return $parsed;
                        }
                    }
                }
                if ($this->looksLikeReportPayload($candidate)) {
                    return $candidate;
                }
            }
            foreach ($candidate as $value) {
                $parsed = $this->parseResponseCandidate($value);
                if ($parsed !== null) {
                    return $parsed;
                }
            }

            return null;
        }

        if (! is_scalar($candidate)) {
            return null;
        }

        $text = trim((string) $candidate);
        if ($text === '') {
            return null;
        }
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        $text = trim($text);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                if ($this->looksLikeReportPayload($decoded)) {
                    return $decoded;
                }
                $nested = $this->parseResponseCandidate($decoded);
                if ($nested !== null) {
                    return $nested;
                }
            }
            if (is_string($decoded) && trim($decoded) !== '') {
                $text = trim($decoded);
                continue;
            }
            if ($attempt === 0) {
                $text = $this->extractJsonObject($text);
                if ($text === null) {
                    break;
                }
            }
        }

        return null;
    }

    private function extractJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($text, $start, $end - $start + 1);
    }

    private function looksLikeReportPayload(array $value): bool
    {
        return isset($value['report_title'])
            || isset($value['executive_summary'])
            || isset($value['documented_results'])
            || isset($value['activity_summaries'])
            || isset($value['key_findings']);
    }
}
