<?php

declare(strict_types=1);

namespace Lumina\Analyzer;

use Lumina\Core\Database;

/**
 * Gestiona una sesión de análisis
 */
class AnalysisSession
{
    private ?int $id = null;
    private ?string $startTime = null;
    private int $chunksCount = 0;
    private int $relationsCount = 0;
    private int $dossiersCount = 0;

    public function __construct(
        private Database $db
    ) {
    }

    /**
     * Inicia una nueva sesión de análisis
     */
    public function start(): void
    {
        // TODO: Implementar en Fase 3
        $this->startTime = date('Y-m-d H:i:s');
    }

    /**
     * Registra la cantidad de chunks procesados
     */
    public function recordChunks(int $count): void
    {
        $this->chunksCount = $count;
    }

    /**
     * Registra la cantidad de relaciones encontradas
     */
    public function recordRelations(int $count): void
    {
        $this->relationsCount = $count;
    }

    /**
     * Registra la cantidad de dossiers generados
     */
    public function recordDossiers(int $count): void
    {
        $this->dossiersCount = $count;
    }

    /**
     * Marca la sesión como completada
     */
    public function complete(): void
    {
        // TODO: Implementar en Fase 3
    }

    /**
     * Marca la sesión como fallida
     */
    public function fail(string $errorMessage): void
    {
        // TODO: Implementar en Fase 3
    }
}
