<?php

declare(strict_types=1);

namespace Lumina\Core;

use Lumina\Parser\Chunker;
use Lumina\Populator\DependencyGraph;
use Lumina\Dossier\DossierGenerator;
use Lumina\Analyzer\AnalysisSession;

/**
 * Clase principal de Lumina.
 * 
 * Coordina el pipeline completo: chunk → relations → dossier
 * Proporciona los métodos de alto nivel para analizar proyectos PHP,
 * generar dossiers y mostrar grafos de dependencias.
 */
class Lumina
{
    /**
     * @param Config $config Configuración de Lumina
     * @param Database|null $db Instancia de base de datos (opcional, se crea si es null)
     */
    public function __construct(
        private Config $config,
        private ?Database $db = null
    ) {
        $this->db = $db ?? new Database($config->getDatabaseConfig());
    }

    /**
     * Analiza un proyecto PHP completo.
     * 
     * Este método coordina todo el pipeline de análisis:
     * 1. Chunker - Extrae SourceChunks de los archivos PHP
     * 2. Populator - Analiza relaciones entre chunks
     * 3. Dossier Generator - Genera dossiers de comprensión
     * 
     * @param string $projectPath Ruta al proyecto a analizar
     * @param int $projectId ID del proyecto en la BD (default: 1)
     * @return array<string, mixed> Estadísticas del análisis
     */
    public function analyzeProject(string $projectPath, int $projectId = 1): array
    {
        echo "🔍 Analizando proyecto: {$projectPath}\n";

        try {
            // Paso 1: Chunker - Extraer SourceChunks
            $chunker = new Chunker($this->config, $this->db);
            $stats = $chunker->analyzeDirectory($projectPath, $projectId);

            echo "✅ Análisis completado:\n";
            echo "   - Archivos encontrados: {$stats['files_found']}\n";
            echo "   - Archivos parseados: {$stats['files_parsed']}\n";
            echo "   - Archivos con error: {$stats['files_failed']}\n";
            echo "   - Chunks extraídos: {$stats['chunks_extracted']}\n";

            if (!empty($stats['errors'])) {
                echo "\n⚠️  Errores encontrados:\n";
                foreach (array_slice($stats['errors'], 0, 5) as $error) {
                    echo "   - {$error['file']}: " . implode(', ', $error['errors']) . "\n";
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Construye el grafo de dependencias para un proyecto.
     * 
     * Analiza todos los SourceChunks del proyecto y detecta las relaciones
     * entre ellos (llamadas, extends, implements, etc.) para construir
     * el grafo de conocimiento en la tabla ChunkRelations.
     * 
     * @param int $projectId ID del proyecto
     * @return array<string, mixed> Estadísticas del proceso
     */
    public function populateRelations(int $projectId): array
    {
        echo "🔗 Construyendo grafo de dependencias para proyecto #{$projectId}\n";

        try {
            $graph = new DependencyGraph($this->db);
            $stats = $graph->buildForProject($projectId);

            echo "✅ Grafo construido:\n";
            echo "   - Chunks analizados: {$stats['chunks_analyzed']}\n";
            echo "   - Relaciones encontradas: {$stats['relations_found']}\n";
            echo "   - Relaciones insertadas: {$stats['relations_inserted']}\n";
            echo "   - Relaciones omitidas: {$stats['relations_skipped']}\n";
            echo "   - Targets no resueltos: " . count($stats['unresolved_targets']) . "\n";

            if (!empty($stats['unresolved_targets'])) {
                $unique = array_unique($stats['unresolved_targets']);
                echo "\n⚠️  Targets no resueltos (primeros 10):\n";
                foreach (array_slice($unique, 0, 10) as $target) {
                    echo "   - {$target}\n";
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Genera dossiers para todos los archivos de un proyecto
     */
    public function generateDossiers(int $projectId): array
    {
        echo "📄 Generando dossiers para proyecto #{$projectId}\n";

        try {
            $generator = new \Lumina\Dossier\DossierGenerator($this->db);
            $stats = $generator->generateForProject($projectId);

            echo "✅ Dossiers generados:\n";
            echo "   - Archivos procesados: {$stats['files_analyzed']}\n";
            echo "   - Dossiers creados: {$stats['dossiers_generated']}\n";
            echo "   - Errores: " . count($stats['errors']) . "\n";

            if (!empty($stats['errors'])) {
                echo "\n⚠️  Errores encontrados:\n";
                foreach (array_slice($stats['errors'], 0, 5) as $error) {
                    echo "   - {$error['file']}: {$error['error']}\n";
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Muestra el grafo de dependencias de un proyecto.
     * 
     * Recupera y muestra las relaciones entre chunks de código
     * para visualizar la estructura del proyecto.
     * 
     * @param int $projectId ID del proyecto
     * @return array<int, array<string, mixed>> Relaciones del grafo
     */
    public function showGraph(int $projectId): array
    {
        echo "📊 Grafo de dependencias del proyecto #{$projectId}:\n";

        $relations = $this->db->fetchAll(
            "SELECT 
                sc.name AS source_name,
                sc.chunk_type AS source_type,
                cr.relation_type,
                tc.name AS target_name,
                tc.chunk_type AS target_type
             FROM ChunkRelations cr
             JOIN SourceChunks sc ON cr.source_chunk_id_ = sc.id_
             JOIN SourceChunks tc ON cr.target_chunk_id_ = tc.id_
             WHERE cr.project_id_ = ?
             ORDER BY cr.relation_type, sc.name
             LIMIT 50",
            [$projectId]
        );

        if (empty($relations)) {
            echo "   No hay relaciones registradas.\n";
            return [];
        }

        foreach ($relations as $rel) {
            echo "   {$rel['source_name']} ({$rel['source_type']}) " .
                 " -> {$rel['relation_type']} -> " .
                 "{$rel['target_name']} ({$rel['target_type']})\n";
        }

        return $relations;
    }

    /**
     * Obtiene la instancia de Database (para uso del CLI y otros componentes)
     * 
     * @return Database Instancia de base de datos para acceso directo
     */
    public function getDb(): Database
    {
        return $this->db;
    }

    /**
     * Enriquece dossiers de un proyecto con IA (Claude)
     *
     * @param int $projectId ID del proyecto
     * @param int $limit Límite de archivos a procesar
     * @return array<string, mixed> Estadísticas del enriquecimiento
     */
    public function enrichWithAi(int $projectId, int $limit = 10): array
    {
        echo "🤖 Enriqueciendo dossiers con IA (Claude)...\n";
        echo "   Límite: {$limit} archivos\n\n";

        try {
            $enricher = new \Lumina\Ai\AiEnricher($this->db, $this->config);
            $stats = $enricher->enrichProject($projectId, $limit);

            if (isset($stats['success']) && $stats['success'] === false) {
                echo "❌ Error: {$stats['error']}\n";
                return $stats;
            }

            echo "✅ Enriquecimiento completado:\n";
            echo "   - Archivos procesados: {$stats['files_processed']}\n";
            echo "   - Archivos enriquecidos: {$stats['files_enriched']}\n";
            echo "   - Archivos con error: {$stats['files_failed']}\n";
            echo "   - Tokens totales: " . number_format($stats['total_tokens']) . "\n";
            echo "   - Costo estimado: $" . number_format($stats['estimated_cost_usd'], 4) . " USD\n";

            if (!empty($stats['errors'])) {
                echo "\n⚠️  Errores encontrados:\n";
                foreach (array_slice($stats['errors'], 0, 5) as $error) {
                    echo "   - {$error['file']}: {$error['error']}\n";
                }
            }

            return $stats;
        } catch (\Throwable $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            throw $e;
        }
    }
}
