<?php

declare(strict_types=1);

namespace Lumina\Parser;

use Lumina\Core\Config;
use Lumina\Model\SourceChunk;

/**
 * Wrapper de nikic/php-parser para analizar código PHP
 */
class PhpParser
{
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Parsea un archivo PHP y retorna el AST
     */
    public function parseFile(string $filePath): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }

    /**
     * Parsea una cadena de código PHP
     */
    public function parseString(string $code): array
    {
        // TODO: Implementar en Fase 3
        return [];
    }
}
