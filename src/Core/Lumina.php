<?php

declare(strict_types=1);

namespace Lumina\Core;

use Lumina\Parser\Chunker;
use Lumina\Populator\RelationAnalyzer;
use Lumina\Dossier\DossierGenerator;
use Lumina\Analyzer\AnalysisSession;

/**
 * Clase principal de Lumina.
 * Coordina el pipeline completo: chunk → relations → dossier
 */
class Lumina
{
    public function __construct(
        private Config $config,
        private ?Database $db = null
    ) {
        $this->db = $db ?? new Database($config->getDatabaseConfig());
    }

    /**
     * Analiza un proyecto PHP completo
     */
    public function analyzeProject(string $projectPath): void
    {
        echo "🔍 Analizando proyecto: {$projectPath}\n";

        $session = new AnalysisSession($this->db);
        $session->start();

        try {
            // Paso 1: Chunker - Extraer SourceChunks
            $chunker = new Chunker($this->config);
            $chunks = $chunker->analyzeDirectory($projectPath);
            $session->recordChunks(count($chunks));

            // Paso 2: Populator - Analizar relaciones
            $analyzer = new RelationAnalyzer($this->db);
            $relations = $analyzer->analyzeAll($chunks);
            $session->recordRelations(count($relations));

            // Paso 3: Dossier Generator - Generar dossiers
            $generator = new DossierGenerator($this->db);
            $dossiers = $generator->generateForProject($projectPath);
            $session->recordDossiers(count($dossiers));

            $session->complete();
            echo "✅ Análisis completado. Dossier generados: " . count($dossiers) . "\n";
        } catch (\Throwable $e) {
            $session->fail($e->getMessage());
            echo "❌ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Genera el dossier de un archivo específico
     */
    public function generateDossier(string $filePath): array
    {
        $generator = new DossierGenerator($this->db);
        return $generator->generateForFile($filePath);
    }

    /**
     * Muestra el grafo de dependencias de un proyecto
     */
    public function showGraph(int $projectId): array
    {
        // TODO: Implementar en Fase 4
        return [];
    }
}
