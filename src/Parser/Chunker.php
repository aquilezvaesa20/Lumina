<?php

declare(strict_types=1);

namespace Lumina\Parser;

use Lumina\Core\Config;
use Lumina\Model\SourceChunk;

/**
 * Extrae SourceChunks de archivos PHP
 */
class Chunker
{
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Analiza un directorio completo y extrae todos los chunks
     */
    public function analyzeDirectory(string $directoryPath): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }

    /**
     * Analiza un archivo individual y extrae sus chunks
     */
    public function analyzeFile(string $filePath): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }
}
