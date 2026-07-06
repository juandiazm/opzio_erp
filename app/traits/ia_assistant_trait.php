<?php

namespace App\traits;

use GuzzleHttp\Client;

trait ia_assistant_trait
{
    private $IAClient = null;

    // =========================================================================
    // HTTP CONNECTION
    // =========================================================================

    private function IA_GetConnection(): void
    {
        $this->IAClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers'  => [
                'Authorization' => 'Bearer ' . env('CHATGPT_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'verify'  => false,
            'timeout' => 300,
        ]);
    }

    // ─── Upload a file to OpenAI Files API ───────────────────────────────────
    public function IA_UploadFile(string $filePath, string $fileName): array
    {
        try {
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers'  => ['Authorization' => 'Bearer ' . env('CHATGPT_API_KEY')],
                'verify'   => false,
                'timeout'  => 120,
            ]);

            $response = $client->post('files', [
                'multipart' => [
                    ['name' => 'purpose', 'contents' => 'user_data'],
                    ['name' => 'file', 'contents' => fopen($filePath, 'r'), 'filename' => $fileName],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['status' => 1, 'file_id' => $data['id']];
        } catch (\Exception $e) {
            info('IA_UploadFile error: ' . $e->getMessage());
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }

    // ─── Delete a file from OpenAI Files API ─────────────────────────────────
    public function IA_DeleteFile(string $openaiFileId): void
    {
        try {
            if ($this->IAClient === null) $this->IA_GetConnection();
            $this->IAClient->delete('files/' . $openaiFileId);
        } catch (\Exception $e) {
            info('IA_DeleteFile error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SECTION JSON SCHEMAS — one strict schema per document section
    // =========================================================================

    private function IA_Section1Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'report_header', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'report_type'     => ['type' => 'string'],
                'platform'        => ['type' => 'string'],
                'brand'           => ['type' => 'string'],
                'period_analyzed' => ['type' => 'string'],
                'report_title'    => ['type' => 'string'],
            ],
            'required'             => ['report_type', 'platform', 'brand', 'period_analyzed', 'report_title'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section2Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'strategy_summary', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'strategy_organization' => ['type' => 'string'],
                'funnel_phases'         => ['type' => 'array', 'items' => ['type' => 'string']],
                'business_intent'       => ['type' => 'string'],
            ],
            'required'             => ['strategy_organization', 'funnel_phases', 'business_intent'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section3Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'positioning_strategy', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'communication_objective' => ['type' => 'string'],
                'segmentation_type'       => ['type' => 'string'],
                'target_profile'          => ['type' => 'string'],
                'optimization_approach'   => ['type' => 'string'],
                'investment_focus'        => ['type' => 'string'],
            ],
            'required'             => ['communication_objective', 'segmentation_type', 'target_profile', 'optimization_approach', 'investment_focus'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section4Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'conversion_strategy', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'conversion_mechanism'    => ['type' => 'string'],
                'ad_formats'              => ['type' => 'array', 'items' => ['type' => 'string']],
                'friction_reduction'      => ['type' => 'string'],
                'behavioral_segmentation' => ['type' => 'string'],
            ],
            'required'             => ['conversion_mechanism', 'ad_formats', 'friction_reduction', 'behavioral_segmentation'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section5Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'engagement_strategy', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'engagement_logic'        => ['type' => 'string'],
                'audience_expansion'      => ['type' => 'string'],
                'remarketing_preparation' => ['type' => 'string'],
            ],
            'required'             => ['engagement_logic', 'audience_expansion', 'remarketing_preparation'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section6Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'campaign_results', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'campaigns' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'name'                        => ['type' => 'string'],
                            'investment'                  => ['type' => 'string'],
                            'reach_or_impressions'        => ['type' => 'string'],
                            'interactions_or_conversions' => ['type' => 'string'],
                            'cost_per_result'             => ['type' => 'string'],
                            'performance_interpretation'  => ['type' => 'string'],
                        ],
                        'required'             => ['name', 'investment', 'reach_or_impressions', 'interactions_or_conversions', 'cost_per_result', 'performance_interpretation'],
                        'additionalProperties' => false,
                    ],
                ],
                'global_metrics' => [
                    'type'       => 'object',
                    'properties' => [
                        'total_investment'  => ['type' => 'string'],
                        'total_reach'       => ['type' => 'number'],
                        'total_impressions' => ['type' => 'number'],
                    ],
                    'required'             => ['total_investment', 'total_reach', 'total_impressions'],
                    'additionalProperties' => false,
                ],
            ],
            'required'             => ['campaigns', 'global_metrics'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section7Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'optimizations', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'adjustments_made'          => ['type' => 'array', 'items' => ['type' => 'string']],
                'budget_changes'            => ['type' => 'string'],
                'segmentation_improvements' => ['type' => 'string'],
                'decisions_during_campaign' => ['type' => 'string'],
            ],
            'required'             => ['adjustments_made', 'budget_changes', 'segmentation_improvements', 'decisions_during_campaign'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section8Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'performance_evolution', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'reach_improvements'   => ['type' => 'string'],
                'interaction_increase' => ['type' => 'string'],
                'format_behavior'      => ['type' => 'string'],
                'key_milestones'       => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required'             => ['reach_improvements', 'interaction_increase', 'format_behavior', 'key_milestones'],
            'additionalProperties' => false,
        ]];
    }

    private function IA_Section9Schema(): array
    {
        return ['type' => 'json_schema', 'name' => 'conclusions', 'strict' => true, 'schema' => [
            'type' => 'object',
            'properties' => [
                'best_performing'         => ['type' => 'string'],
                'most_efficient_formats'  => ['type' => 'string'],
                'best_responding_segment' => ['type' => 'string'],
                'future_opportunities'    => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required'             => ['best_performing', 'most_efficient_formats', 'best_responding_segment', 'future_opportunities'],
            'additionalProperties' => false,
        ]];
    }

    // =========================================================================
    // SECTION PROMPTS + SCHEMAS MAP
    // =========================================================================

    private function IA_GetSectionDefs(): array
    {
        return [
            'report_header' => [
                $this->IA_Section1Schema(),
                "Genera la SECCIÓN 1 — TÍTULO DEL INFORME.\n" .
                "Con base en el archivo analizado, extrae: tipo de informe, plataforma publicitaria, nombre exacto de la marca o cliente, período analizado.\n" .
                "Construye un título profesional y completo para el documento.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'strategy_summary' => [
                $this->IA_Section2Schema(),
                "Genera la SECCIÓN 2 — RESUMEN DE LA ESTRATEGIA.\n" .
                "Explica con profundidad y tono ejecutivo:\n" .
                "- Cómo se organizó la estrategia publicitaria en su conjunto durante el período.\n" .
                "- Qué fases del embudo de conversión se trabajaron (awareness, consideración, conversión, interacción, etc.).\n" .
                "- Cuál fue la intención general de negocio detrás de las campañas.\n" .
                "REGLA CRÍTICA: NO incluyas métricas ni resultados numéricos. Solo lógica estratégica y narrativa.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'positioning_strategy' => [
                $this->IA_Section3Schema(),
                "Genera la SECCIÓN 3 — ESTRATEGIA DE POSICIONAMIENTO (AWARENESS).\n" .
                "Describe con profundidad analítica y tono profesional:\n" .
                "- El objetivo de comunicación de las campañas orientadas a visibilidad o reconocimiento.\n" .
                "- El tipo de segmentación utilizada (geográfica, demográfica, por intereses, lookalike, etc.).\n" .
                "- El perfil general del público objetivo al que se buscó impactar.\n" .
                "- El tipo de optimización de entrega aplicada (alcance máximo, impresiones, ThruPlay, etc.).\n" .
                "- El enfoque de inversión en esta etapa del embudo.\n" .
                "REGLA CRÍTICA: No incluyas resultados ni métricas numéricas en esta sección.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'conversion_strategy' => [
                $this->IA_Section4Schema(),
                "Genera la SECCIÓN 4 — ESTRATEGIA DE CONVERSIÓN.\n" .
                "Describe con solidez y claridad:\n" .
                "- El mecanismo de conversión utilizado (formularios Lead Ads, mensajes por WhatsApp/Messenger, tráfico al sitio web, llamadas, etc.).\n" .
                "- Los formatos de anuncios empleados para generar acción directa.\n" .
                "- De qué manera se buscó reducir la fricción en el proceso de conversión.\n" .
                "- Los criterios de segmentación comportamental utilizados (remarketing, audiencias personalizadas, compradores, etc.).\n" .
                "REGLA CRÍTICA: No incluyas métricas ni datos numéricos en esta sección.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'engagement_strategy' => [
                $this->IA_Section5Schema(),
                "Genera la SECCIÓN 5 — ESTRATEGIA DE INTERACCIÓN O CRECIMIENTO DE AUDIENCIA.\n" .
                "Explica con detalle:\n" .
                "- La lógica detrás de las acciones orientadas a generar engagement con el contenido de la marca.\n" .
                "- Cómo se buscó ampliar la audiencia activa de la marca en Meta.\n" .
                "- De qué manera esta estrategia prepara el terreno para futuros esfuerzos de remarketing o retargeting.\n" .
                "REGLA CRÍTICA: No incluyas resultados numéricos en esta sección.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'campaign_results' => [
                $this->IA_Section6Schema(),
                "Genera la SECCIÓN 6 — RESULTADOS POR CAMPAÑA.\n" .
                "Para CADA campaña o conjunto de anuncios presente en el archivo, presenta:\n" .
                "- Nombre o identificación de la campaña.\n" .
                "- Inversión total ejecutada en esa campaña.\n" .
                "- Alcance e impresiones obtenidos (combínalos en un campo descriptivo si aplica).\n" .
                "- Interacciones o conversiones generadas, según el objetivo de la campaña.\n" .
                "- Costo por resultado obtenido.\n" .
                "- Una interpretación sustanciosa del desempeño: si fue eficiente, por qué, qué indica, qué se puede inferir.\n" .
                "Incluye también las métricas globales consolidadas del período completo.\n" .
                "REGLA CRÍTICA: Esta es la ÚNICA sección donde van métricas. No expliques estrategia nueva aquí.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'optimizations' => [
                $this->IA_Section7Schema(),
                "Genera la SECCIÓN 7 — AJUSTES Y OPTIMIZACIONES REALIZADAS DURANTE LA EJECUCIÓN.\n" .
                "Describe con detalle la gestión activa de las campañas:\n" .
                "- Lista de ajustes realizados durante el período (cambios en creatividades, horarios, ubicaciones, etc.).\n" .
                "- Cambios de presupuesto aplicados y su lógica.\n" .
                "- Mejoras de segmentación introducidas durante la ejecución.\n" .
                "- Decisiones clave tomadas durante la pauta y su justificación.\n" .
                "REGLA CRÍTICA: No repitas métricas detalladas de la sección anterior.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'performance_evolution' => [
                $this->IA_Section8Schema(),
                "Genera la SECCIÓN 8 — EVOLUCIÓN DEL RENDIMIENTO DURANTE EL PERÍODO.\n" .
                "Muestra los cambios relevantes en el desempeño a lo largo del tiempo:\n" .
                "- Cómo evolucionó el alcance de las campañas durante el período.\n" .
                "- Si hubo incremento en la interacción o engagement a lo largo del tiempo.\n" .
                "- Qué formatos específicos mostraron mejor comportamiento y por qué.\n" .
                "- Los hitos más relevantes del período (picos de rendimiento, caídas, momentos clave).\n" .
                "REGLA CRÍTICA: No repitas la estrategia ni listes métricas detalladas nuevamente.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
            'conclusions' => [
                $this->IA_Section9Schema(),
                "Genera la SECCIÓN 9 — CONCLUSIONES ESTRATÉGICAS.\n" .
                "Sintetiza los aprendizajes y recomendaciones del período con visión ejecutiva:\n" .
                "- Qué elementos, campañas o enfoques funcionaron mejor y por qué.\n" .
                "- Qué formatos demostraron mayor eficiencia en relación costo-resultado.\n" .
                "- Qué segmento de audiencia respondió mejor a los estímulos publicitarios.\n" .
                "- Las oportunidades concretas de optimización para el siguiente período (lista de acciones recomendadas).\n" .
                "REGLA CRÍTICA: No repitas métricas detalladas. Enfócate en aprendizajes, patrones y proyecciones.\n" .
                "Responde únicamente con el JSON estructurado solicitado.",
            ],
        ];
    }

    // =========================================================================
    // LOW-LEVEL HELPERS
    // =========================================================================

    // Step 0: Upload the file and establish analysis context (no structured output)
    private function IA_AnalyzeFile(string $openaiFileId, string $clientName, string $period): array
    {
        if ($this->IAClient === null) $this->IA_GetConnection();

        $systemPrompt = <<<PROMPT
Eres un analista experto en marketing digital especializado en publicidad Meta (Facebook e Instagram).
Tu misión es analizar en profundidad archivos de datos exportados de Meta Ads Business Suite o Meta Ads Manager
y construir informes profesionales, estratégicos y orientados a decisiones ejecutivas.

El archivo que recibirás pertenece al cliente "{$clientName}" para el período "{$period}".

Analiza exhaustivamente:
- Nombres de campañas y su propósito inferido (awareness, conversión, interacción).
- Métricas clave: inversión total por campaña, alcance, impresiones, resultados, costo por resultado, frecuencia.
- Fechas de inicio y fin de cada campaña o período activo.
- Patrones de rendimiento: tendencias, qué funcionó, qué no, variaciones a lo largo del tiempo.
- Segmentaciones y formatos de anuncios si están disponibles en los datos.
- Cualquier ajuste visible en el presupuesto o configuración a lo largo del período.

Este análisis será la base para generar un informe estructurado y profesional en 9 secciones.
Confirma que has procesado el archivo respondiendo únicamente:
"Archivo analizado correctamente. Listo para generar el informe de {$clientName} — {$period}."
PROMPT;

        $payload = [
            'model' => 'gpt-4.1',
            'input' => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'input_file', 'file_id' => $openaiFileId],
                        ['type' => 'input_text', 'text'    => "Analiza este archivo de Meta Ads del cliente {$clientName}, período {$period}."],
                    ],
                ],
            ],
            'store' => true,
        ];

        $response = $this->IAClient->post('responses', ['json' => $payload]);
        return json_decode($response->getBody()->getContents(), true);
    }

    // Single section call chained via previous_response_id
    private function IA_CallSection(string $previousResponseId, string $prompt, array $schema): array
    {
        if ($this->IAClient === null) $this->IA_GetConnection();

        $payload = [
            'model'                => 'gpt-4.1',
            'previous_response_id' => $previousResponseId,
            'input'                => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'text'  => ['format' => $schema],
            'store' => true,
        ];

        $response = $this->IAClient->post('responses', ['json' => $payload]);
        return json_decode($response->getBody()->getContents(), true);
    }

    // Extract parsed JSON from a Responses API data array
    private function IA_ExtractJson(array $data): ?array
    {
        if (empty($data['id'])) return null;

        $text = '';
        foreach ($data['output'] ?? [] as $item) {
            if ($item['type'] === 'message' && !empty($item['content'])) {
                foreach ($item['content'] as $content) {
                    if ($content['type'] === 'output_text') {
                        $text = $content['text'];
                        break 2;
                    }
                }
            }
        }

        $decoded = json_decode($text, true);
        return (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) ? $decoded : null;
    }

    // Run all 9 sections sequentially, chaining from $initialResponseId
    private function IA_RunSections(string $initialResponseId): array
    {
        $previousId  = $initialResponseId;
        $totalInput  = 0;
        $totalOutput = 0;
        $sections    = [];

        foreach ($this->IA_GetSectionDefs() as $key => [$schema, $prompt]) {
            $data = $this->IA_CallSection($previousId, $prompt, $schema);

            if (empty($data['id'])) {
                return [
                    'status'  => 0,
                    'message' => "Error al generar la sección '{$key}': " . json_encode($data),
                ];
            }

            $parsed = $this->IA_ExtractJson($data);
            if ($parsed === null) {
                return [
                    'status'  => 0,
                    'message' => "No se pudo parsear el JSON de la sección '{$key}'.",
                ];
            }

            $sections[$key] = $parsed;
            $previousId      = $data['id'];
            $totalInput     += $data['usage']['input_tokens']  ?? 0;
            $totalOutput    += $data['usage']['output_tokens'] ?? 0;
        }

        return [
            'status'        => 1,
            'response_id'   => $previousId,
            'report_json'   => $sections,
            'input_tokens'  => $totalInput,
            'output_tokens' => $totalOutput,
        ];
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    // Generate full report: 1 analysis call + 9 section calls
    public function IA_GenerateMarketingReport(
        string $openaiFileId,
        string $clientName,
        string $period
    ): array {
        try {
            set_time_limit(600);
            if ($this->IAClient === null) $this->IA_GetConnection();

            // Step 0: analyze file and establish context
            $analysisData = $this->IA_AnalyzeFile($openaiFileId, $clientName, $period);
            if (empty($analysisData['id'])) {
                return ['status' => 0, 'message' => 'Error al analizar el archivo: ' . json_encode($analysisData)];
            }

            $analysisInTokens  = $analysisData['usage']['input_tokens']  ?? 0;
            $analysisOutTokens = $analysisData['usage']['output_tokens'] ?? 0;

            // Steps 1–9: generate each section sequentially
            $result = $this->IA_RunSections($analysisData['id']);
            if ($result['status'] !== 1) {
                return $result;
            }

            return [
                'status'        => 1,
                'response_id'   => $result['response_id'],
                'report_json'   => $result['report_json'],
                'input_tokens'  => $analysisInTokens  + $result['input_tokens'],
                'output_tokens' => $analysisOutTokens + $result['output_tokens'],
            ];
        } catch (\Exception $e) {
            info('IA_GenerateMarketingReport error: ' . $e->getMessage());
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }

    // Regenerate full report with user feedback: 1 feedback context call + 9 section calls
    public function IA_RegenerateReport(
        string $previousResponseId,
        string $feedback
    ): array {
        try {
            set_time_limit(600);
            if ($this->IAClient === null) $this->IA_GetConnection();

            // Step R0: send the feedback, chained from the last section response
            $feedbackPayload = [
                'model'                => 'gpt-4.1',
                'previous_response_id' => $previousResponseId,
                'input' => [
                    [
                        'role'    => 'user',
                        'content' => "El usuario ha revisado el informe y proporciona el siguiente feedback para incorporar en la regeneración completa del documento:\n\n{$feedback}\n\nTen en cuenta este feedback al regenerar cada sección. Confirma con: \"Feedback recibido. Regenerando el informe con los ajustes solicitados.\"",
                    ],
                ],
                'store' => true,
            ];

            $feedbackResponse = $this->IAClient->post('responses', ['json' => $feedbackPayload]);
            $feedbackData     = json_decode($feedbackResponse->getBody()->getContents(), true);

            if (empty($feedbackData['id'])) {
                return ['status' => 0, 'message' => 'Error al procesar el feedback.'];
            }

            $fbInTokens  = $feedbackData['usage']['input_tokens']  ?? 0;
            $fbOutTokens = $feedbackData['usage']['output_tokens'] ?? 0;

            // Steps 1–9: re-generate each section with feedback context
            $result = $this->IA_RunSections($feedbackData['id']);
            if ($result['status'] !== 1) {
                return $result;
            }

            return [
                'status'        => 1,
                'response_id'   => $result['response_id'],
                'report_json'   => $result['report_json'],
                'input_tokens'  => $fbInTokens  + $result['input_tokens'],
                'output_tokens' => $fbOutTokens + $result['output_tokens'],
            ];
        } catch (\Exception $e) {
            info('IA_RegenerateReport error: ' . $e->getMessage());
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }
}
