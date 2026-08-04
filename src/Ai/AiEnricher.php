<?php

declare(strict_types=1);

namespace Lumina\Ai;

use Lumina\Core\Database;
use Lumina\Core\Config;

class AiEnricher
{
    private Database $db;
    private AiClientInterface $client;
    private PromptBuilder $promptBuilder;
    private int $enrichedCount = 0;
    private array $errors = [];

    public function __construct(Database $db, Config $config)
    {
        $this->db = $db;
        $this->promptBuilder = new PromptBuilder();
        
        // Por ahora solo Claude, en el futuro se puede agregar OpenAI, Gemini, etc.
        $this->client = new ClaudeClient($config);
    }

    /**
     * Enriquece todos los dossiers de un proyecto con IA
     *
     * @param int $projectId ID del proyecto
     * @param int $limit Límite de archivos a procesar
     * @return array<string, mixed>
     */
    public function enrichProject(int $projectId, int $limit = 10): array
    {
        if (!$this->client->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Cliente de IA no configurado. Configura ANTHROPIC_API_KEY en .env',
            ];
        }

        $stats = [
            'files_processed' => 0,
            'files_enriched' => 0,
            'files_failed' => 0,
            'total_tokens' => 0,
            'estimated_cost_usd' => 0.0,
            'errors' => [],
        ];

        // Obtener dossiers pendientes de enriquecer
        $dossiers = $this->db->fetchAll(
            "SELECT fd.id_, fd.source_id_, fd.confidence_score,
                    ps.filename, ps.s3_key, ps.size_bytes
             FROM FileDossiers fd
             JOIN ProjectSources ps ON fd.source_id_ = ps.id_
             WHERE fd.project_id_ = ?
             AND (fd.ai_generated = 0 OR fd.confidence_score < 0.7)
             ORDER BY fd.confidence_score ASC
             LIMIT ?",
            [$projectId, $limit]
        );

        foreach ($dossiers as $dossier) {
            $stats['files_processed']++;
            
            try {
                $result = $this->enrichDossier(
                    (int)$dossier['id_'],
                    (int)$dossier['source_id_'],
                    $projectId
                );
                
                if ($result['success']) {
                    $stats['files_enriched']++;
                    $stats['total_tokens'] += $result['tokens']['total'];
                    $stats['estimated_cost_usd'] += $result['cost_usd'];
                } else {
                    $stats['files_failed']++;
                    $stats['errors'][] = [
                        'file' => $dossier['filename'],
                        'error' => $result['error'],
                    ];
                }
                
                // Rate limiting: espera 1 segundo entre requests
                sleep(1);
                
            } catch (\Throwable $e) {
                $stats['files_failed']++;
                $stats['errors'][] = [
                    'file' => $dossier['filename'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Enriquece un dossier individual
     *
     * @param int $dossierId ID del dossier
     * @param int $sourceId ID del source
     * @param int $projectId ID del proyecto
     * @return array<string, mixed>
     */
    public function enrichDossier(int $dossierId, int $sourceId, int $projectId): array
    {
        // 1. Obtener datos del archivo
        $source = $this->db->fetchOne(
            "SELECT * FROM ProjectSources WHERE id_ = ?",
            [$sourceId]
        );

        if (!$source) {
            return ['success' => false, 'error' => 'Source not found'];
        }

        // 2. Obtener chunks del archivo
        $chunks = $this->db->fetchAll(
            "SELECT * FROM SourceChunks WHERE source_id_ = ? AND project_id_ = ?",
            [$sourceId, $projectId]
        );

        // 3. Obtener relaciones del archivo
        $chunkIds = array_column($chunks, 'id_');
        $relations = [];
        if (!empty($chunkIds)) {
            $placeholders = implode(',', array_fill(0, count($chunkIds), '?'));
            $relations = $this->db->fetchAll(
                "SELECT cr.*, sc.name AS target_name, sc.chunk_type AS target_type
                 FROM ChunkRelations cr
                 JOIN SourceChunks sc ON cr.target_chunk_id_ = sc.id_
                 WHERE cr.source_chunk_id_ IN ({$placeholders})",
                $chunkIds
            );
        }

        // 4. Obtener dossier estático actual
        $staticDossier = $this->db->fetchOne(
            "SELECT * FROM FileDossiers WHERE id_ = ?",
            [$dossierId]
        );

        // 5. Construir prompt
        $prompt = $this->promptBuilder->buildEnrichmentPrompt(
            $source,
            $chunks,
            $relations,
            $staticDossier
        );

        // 6. Llamar a la IA
        try {
            $response = $this->client->complete($prompt, [
                'temperature' => 0.3,
                'max_tokens' => 4096,
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'AI API error: ' . $e->getMessage()];
        }

        // 7. Parsear respuesta JSON
        $aiData = $this->parseAiResponse($response['content']);
        
        if ($aiData === null) {
            return ['success' => false, 'error' => 'Failed to parse AI response as JSON'];
        }

        // 8. Actualizar dossier en BD
        try {
            $this->db->query(
                "UPDATE FileDossiers SET 
                 where_is = ?,
                 interacts_with = ?,
                 what_does = ?,
                 why_exists = ?,
                 how_does = ?,
                 failure_causes = ?,
                 ai_generated = 1,
                 confidence_score = ?,
                 generated_by = ?,
                 meta = JSON_MERGE_PATCH(COALESCE(meta, JSON_OBJECT()), ?),
                 version = version + 1,
                 updated_at = NOW()
                 WHERE id_ = ?",
                [
                    $aiData['where_is'] ?? $staticDossier['where_is'],
                    json_encode($aiData['interacts_with'] ?? [], JSON_UNESCAPED_UNICODE),
                    $aiData['what_does'] ?? $staticDossier['what_does'],
                    $aiData['why_exists'] ?? $staticDossier['why_exists'],
                    $aiData['how_does'] ?? $staticDossier['how_does'],
                    is_array($aiData['failure_causes'] ?? null) ? implode("\n", $aiData['failure_causes']) : ($staticDossier['failure_causes'] ?? ''),
                    $aiData['confidence_score'] ?? 0.85,
                    $this->client->getProviderName() . ':' . ($response['model'] ?? 'unknown'),
                    json_encode([
                        'ai_enriched_at' => date('c'),
                        'tokens_used' => $response['usage'],
                        'key_insights' => $aiData['key_insights'] ?? [],
                    ]),
                    $dossierId,
                ]
            );
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'DB update error: ' . $e->getMessage()];
        }

        // 9. Calcular costo estimado (Claude 3.5 Sonnet pricing)
        $costUsd = $this->estimateCost($response['usage']);

        return [
            'success' => true,
            'tokens' => [
                'input' => $response['usage']['input_tokens'] ?? 0,
                'output' => $response['usage']['output_tokens'] ?? 0,
                'total' => ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0),
            ],
            'cost_usd' => $costUsd,
            'confidence_score' => $aiData['confidence_score'] ?? 0.85,
        ];
    }

    /**
     * Parsea la respuesta de la IA como JSON
     *
     * @param string $content Contenido de la respuesta
     * @return array<string, mixed>|null
     */
    private function parseAiResponse(string $content): ?array
    {
        // Intentar extraer JSON del contenido (puede venir con markdown)
        $content = trim($content);
        
        // Remover bloques de código markdown si existen
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }
        
        // Intentar parsear directamente
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
        
        // Buscar JSON en el texto
        if (preg_match('/\{[\s\S]*"where_is"[\s\S]*\}/', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }
        
        return null;
    }

    /**
     * Estima el costo en USD basado en tokens usados
     * Pricing Claude 3.5 Sonnet: $3/M input, $15/M output
     *
     * @param array<string, int> $usage
     * @return float
     */
    private function estimateCost(array $usage): float
    {
        $inputCost = ($usage['input_tokens'] ?? 0) * 0.000003; // $3 per million
        $outputCost = ($usage['output_tokens'] ?? 0) * 0.000015; // $15 per million
        return $inputCost + $outputCost;
    }
}
