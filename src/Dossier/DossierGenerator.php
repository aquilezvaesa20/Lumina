<?php

declare(strict_types=1);

namespace Lumina\Dossier;

use Lumina\Core\Database;

/**
 * Orquestador principal que coordina todo el proceso de generación de dossiers
 */
class DossierGenerator
{
    private Database $db;
    private QuestionAnswerer $answerer;
    private DossierTemplate $template;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->answerer = new QuestionAnswerer($db);
        $this->template = new DossierTemplate();
    }

    /**
     * Genera dossiers para todos los archivos de un proyecto
     */
    public function generateForProject(int $projectId): array
    {
        $stats = [
            'files_analyzed' => 0,
            'chunks_extracted' => 0,
            'relations_found' => 0,
            'dossiers_generated' => 0,
            'dossiers_updated' => 0,
            'errors' => [],
        ];

        // Obtener estadísticas del proyecto
        $chunkCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM SourceChunks WHERE project_id_ = ?",
            [$projectId]
        );
        $relationCount = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM ChunkRelations WHERE project_id_ = ?",
            [$projectId]
        );

        $stats['chunks_extracted'] = (int)($chunkCount['total'] ?? 0);
        $stats['relations_found'] = (int)($relationCount['total'] ?? 0);

        // Obtener todos los archivos indexados del proyecto
        $sources = $this->db->fetchAll(
            "SELECT * FROM ProjectSources WHERE project_id_ = ? AND status = 'indexed'",
            [$projectId]
        );

        $stats['files_analyzed'] = count($sources);
        $dossiersForSkill = [];

        foreach ($sources as $source) {
            try {
                $result = $this->generateForSource((int)$source['id_'], $projectId);
                
                if ($result['success']) {
                    $stats['dossiers_generated']++;
                    $dossiersForSkill[] = $result['dossier_summary'];
                } else {
                    $stats['errors'][] = [
                        'file' => $source['filename'],
                        'error' => $result['error'],
                    ];
                }
            } catch (\Throwable $e) {
                $stats['errors'][] = [
                    'file' => $source['filename'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Generar SKILL.md
        try {
            $project = $this->db->fetchOne(
                "SELECT name FROM Projects WHERE id_ = ?",
                [$projectId]
            );
            $projectName = $project['name'] ?? "Proyecto #{$projectId}";

            $skillMd = $this->template->renderSkillMd($projectName, $dossiersForSkill, $stats);
            
            // Guardar SKILL.md en la raíz del proyecto analizado
            $skillPath = getcwd() . '/SKILL.md';
            file_put_contents($skillPath, $skillMd);
            
            echo "📄 SKILL.md generado en: {$skillPath}\n";
        } catch (\Throwable $e) {
            $stats['errors'][] = [
                'file' => 'SKILL.md',
                'error' => $e->getMessage(),
            ];
        }

        return $stats;
    }

    /**
     * Genera el dossier para un archivo específico
     */
    public function generateForSource(int $sourceId, int $projectId): array
    {
        // 1. Obtener información del archivo
        $source = $this->db->fetchOne(
            "SELECT * FROM ProjectSources WHERE id_ = ?",
            [$sourceId]
        );

        if (!$source) {
            return ['success' => false, 'error' => 'Source not found'];
        }

        // 2. Obtener todos los chunks del archivo
        $chunks = $this->db->fetchAll(
            "SELECT * FROM SourceChunks WHERE source_id_ = ? AND project_id_ = ?",
            [$sourceId, $projectId]
        );

        if (empty($chunks)) {
            return ['success' => false, 'error' => 'No chunks found for this source'];
        }

        // 3. Responder las 5 preguntas
        $whereIs = $this->answerer->answerWhereIs($source, $chunks);
        $interactsWith = $this->answerer->answerInteractsWith($sourceId, $projectId);
        $whatDoes = $this->answerer->answerWhatDoes($source, $chunks);
        $whyExists = $this->answerer->answerWhyExists($source, $chunks);
        $howDoes = $this->answerer->answerHowDoes($source, $chunks);
        $failureCauses = $this->answerer->answerFailureCauses($sourceId, $projectId);

        // 4. Calcular confidence score basado en la calidad del análisis
        $confidenceScore = $this->calculateConfidence($chunks, $interactsWith);

        // 5. Almacenar en FileDossiers
        try {
            // Verificar si ya existe (para actualizar o insertar)
            $existing = $this->db->fetchOne(
                "SELECT id_, version FROM FileDossiers WHERE source_id_ = ?",
                [$sourceId]
            );

            $data = [
                'source_id_' => $sourceId,
                'project_id_' => $projectId,
                'where_is' => $whereIs,
                'interacts_with' => json_encode($interactsWith, JSON_UNESCAPED_UNICODE),
                'what_does' => $whatDoes,
                'why_exists' => $whyExists,
                'how_does' => $howDoes,
                'failure_causes' => $failureCauses,
                'ai_generated' => 0, // Análisis estático por ahora
                'confidence_score' => $confidenceScore,
                'generated_by' => 'lumina-static-analyzer',
                'meta' => json_encode([
                    'chunks_count' => count($chunks),
                    'outgoing_deps' => $interactsWith['outgoing_count'] ?? 0,
                    'incoming_deps' => $interactsWith['incoming_count'] ?? 0,
                ]),
            ];

            if ($existing) {
                // Actualizar dossier existente
                $this->db->query(
                    "UPDATE FileDossiers SET 
                     where_is = ?, interacts_with = ?, what_does = ?, why_exists = ?,
                     how_does = ?, failure_causes = ?, ai_generated = ?,
                     confidence_score = ?, generated_by = ?, meta = ?,
                     version = version + 1
                     WHERE source_id_ = ?",
                    array_merge(
                        array_values($data),
                        [$sourceId]
                    )
                );
            } else {
                // Insertar nuevo dossier
                $this->db->insert('FileDossiers', $data);
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Failed to save dossier: ' . $e->getMessage()];
        }

        // 6. Generar markdown del dossier individual
        $markdown = $this->template->render(
            $source,
            $whereIs,
            $interactsWith,
            $whatDoes,
            $whyExists,
            $howDoes,
            $failureCauses,
            false,
            $confidenceScore
        );

        // Guardar dossier individual en /dossiers/
        $dossierDir = getcwd() . '/dossiers';
        if (!is_dir($dossierDir)) {
            mkdir($dossierDir, 0755, true);
        }
        
        $dossierFilename = str_replace(
            ['/', '\\', '.php'],
            ['_', '_', '.md'],
            $source['filename']
        );
        $dossierPath = $dossierDir . '/' . $dossierFilename;
        file_put_contents($dossierPath, $markdown);

        return [
            'success' => true,
            'dossier_summary' => [
                'filename' => $source['filename'],
                'file_type' => $this->detectFileType($chunks),
                'what_does_summary' => $this->truncate($whatDoes, 100),
                'interacts_summary' => $interactsWith['summary'] ?? 'N/A',
                'outgoing_count' => $interactsWith['outgoing_count'] ?? 0,
                'incoming_count' => $interactsWith['incoming_count'] ?? 0,
                'confidence' => $confidenceScore,
            ],
        ];
    }

    /**
     * Genera dossier para un archivo específico por ruta (para CLI)
     */
    public function generateForFile(string $filePath, int $projectId = 1): array
    {
        // Buscar el source por filename o s3_key
        $source = $this->db->fetchOne(
            "SELECT * FROM ProjectSources 
             WHERE project_id_ = ? AND (filename = ? OR s3_key LIKE ?)
             LIMIT 1",
            [$projectId, basename($filePath), '%' . basename($filePath)]
        );

        if (!$source) {
            return [
                'success' => false,
                'error' => "File not found in project: {$filePath}",
            ];
        }

        return $this->generateForSource((int)$source['id_'], $projectId);
    }

    // ==========================================
    // MÉTODOS PRIVADOS
    // ==========================================

    private function calculateConfidence(array $chunks, array $interactsWith): float
    {
        $score = 0.5; // Base

        // Tiene docblocks → más confiable
        $chunksWithDocblock = array_filter($chunks, fn($c) => !empty($c['docblock']));
        if (count($chunksWithDocblock) > 0) {
            $score += 0.15;
        }

        // Tiene relaciones confirmadas → más confiable
        $outgoing = $interactsWith['outgoing_count'] ?? 0;
        $incoming = $interactsWith['incoming_count'] ?? 0;
        if ($outgoing > 0 || $incoming > 0) {
            $score += 0.15;
        }

        // Tiene type hints → más confiable
        $chunksWithTypes = array_filter($chunks, fn($c) => !empty($c['return_type']));
        if (count($chunksWithTypes) > 0) {
            $score += 0.1;
        }

        // Tiene nombre descriptivo → más confiable
        $mainChunk = null;
        foreach ($chunks as $chunk) {
            if (in_array($chunk['chunk_type'], ['class', 'interface', 'trait', 'function'])) {
                $mainChunk = $chunk;
                break;
            }
        }
        if ($mainChunk && !empty($mainChunk['name'])) {
            $score += 0.1;
        }

        return min(1.0, round($score, 2));
    }

    private function detectFileType(array $chunks): string
    {
        foreach (['class', 'interface', 'trait', 'function'] as $type) {
            foreach ($chunks as $chunk) {
                if ($chunk['chunk_type'] === $type) {
                    return $type;
                }
            }
        }
        return 'unknown';
    }

    private function truncate(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
}
