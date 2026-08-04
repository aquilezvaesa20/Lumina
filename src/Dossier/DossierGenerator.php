<?php

declare(strict_types=1);

namespace Lumina\Dossier;

use Lumina\Core\Database;
use Lumina\Model\FileDossier;

/**
 * Genera los dossiers de archivos (5 preguntas)
 */
class DossierGenerator
{
    public function __construct(
        private Database $db
    ) {
    }

    /**
     * Genera dossiers para todos los archivos de un proyecto
     */
    public function generateForProject(string $projectPath): array
    {
        // TODO: Implementar en Fase 5
        return [];
    }

    /**
     * Genera el dossier de un archivo específico
     */
    public function generateForFile(string $filePath): array
    {
        // TODO: Implementar en Fase 5
        return [];
    }
}
