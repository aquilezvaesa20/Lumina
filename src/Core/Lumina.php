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
