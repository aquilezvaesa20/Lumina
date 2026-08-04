<?php

declare(strict_types=1);

namespace Lumina\Analyzer;

/**
 * Analiza solo los cambios incrementales
 */
class IncrementalAnalyzer
{
    /**
     * Detecta archivos modificados desde la última sesión
     */
    public function getModifiedFiles(int $projectId, ?string $sinceTimestamp = null): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }

    /**
     * Analiza solo los archivos modificados
     */
    public function analyzeChanges(array $modifiedFiles): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }
}
