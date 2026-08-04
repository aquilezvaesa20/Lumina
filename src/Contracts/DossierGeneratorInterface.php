<?php

declare(strict_types=1);

namespace Lumina\Contracts;

/**
 * Interface para generadores de dossiers
 */
interface DossierGeneratorInterface
{
    /**
     * Genera un dossier para un archivo
     */
    public function generate(string $filePath): array;

    /**
     * Genera dossiers para un proyecto completo
     */
    public function generateForProject(string $projectPath): array;
}
