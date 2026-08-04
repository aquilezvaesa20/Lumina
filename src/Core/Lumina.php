<?php

declare(strict_types=1);

namespace Lumina\Core;

use Lumina\Parser\Chunker;
use Lumina\Populator\RelationAnalyzer;
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
     * @return void
     * 
     * @todo Implementar en Fase 3 (Chunker)
     */
    public function analyzeProject(string $projectPath): void
    {
        echo "🔍 Analizando proyecto: {$projectPath}\n";

        // TODO: Implementar en Fase 3
        // $session = new AnalysisSession($this->db);
        // $session->start();
        //
        // try {
        //     $chunker = new Chunker($this->config);
        //     $chunks = $chunker->analyzeDirectory($projectPath);
        //     $session->recordChunks(count($chunks));
        //
        //     $analyzer = new RelationAnalyzer($this->db);
        //     $relations = $analyzer->analyzeAll($chunks);
        //     $session->recordRelations(count($relations));
        //
        //     $generator = new DossierGenerator($this->db);
        //     $dossiers = $generator->generateForProject($projectPath);
        //     $session->recordDossiers(count($dossiers));
        //
        //     $session->complete();
        //     echo "✅ Análisis completado. Dossier generados: " . count($dossiers) . "\n";
        // } catch (\Throwable $e) {
        //     $session->fail($e->getMessage());
        //     echo "❌ Error: " . $e->getMessage() . "\n";
        //     throw $e;
        // }

        echo "⚠️  Método stub - Será implementado en Fase 3\n";
    }

    /**
     * Genera el dossier de un archivo específico.
     * 
     * El dossier contiene información completa sobre el archivo:
     * - ¿Dónde está? (whereIs)
     * - ¿Con qué interactúa? (interactsWith)
     * - ¿Qué hace? (whatDoes)
     * - ¿Por qué existe? (whyExists)
     * - ¿Cómo lo hace? (howDoes)
     * - Causas de fallo conocidas (failureCauses)
     * 
     * @param string $filePath Ruta al archivo PHP
     * @return array<string, mixed> Datos del dossier
     * 
     * @todo Implementar en Fase 5 (Dossier)
     */
    public function generateDossier(string $filePath): array
    {
        // TODO: Implementar en Fase 5
        // $generator = new DossierGenerator($this->db);
        // return $generator->generateForFile($filePath);

        echo "⚠️  Método stub - Será implementado en Fase 5\n";
        return [];
    }

    /**
     * Muestra el grafo de dependencias de un proyecto.
     * 
     * Recupera y muestra las relaciones entre chunks de código
     * para visualizar la estructura del proyecto.
     * 
     * @param int $projectId ID del proyecto
     * @return array<int, array<string, mixed>> Relaciones del grafo
     * 
     * @todo Implementar en Fase 4 (Populator/Grafo)
     */
    public function showGraph(int $projectId): array
    {
        // TODO: Implementar en Fase 4
        echo "⚠️  Método stub - Será implementado en Fase 4\n";
        return [];
    }
}
