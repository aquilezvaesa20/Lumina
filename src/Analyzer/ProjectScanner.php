<?php

declare(strict_types=1);

namespace Lumina\Analyzer;

/**
 * Escanea un proyecto completo en busca de archivos PHP
 */
class ProjectScanner
{
    /**
     * Escanea un directorio y retorna todos los archivos PHP
     */
    public function scan(string $directoryPath, array $options = []): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }

    /**
     * Verifica si un archivo debe ser excluido del análisis
     */
    public function shouldExclude(string $filePath, array $excludeDirs): bool
    {
        // TODO: Implementar en Fase 3
        return false;
    }
}
