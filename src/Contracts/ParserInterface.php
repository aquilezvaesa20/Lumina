<?php

declare(strict_types=1);

namespace Lumina\Contracts;

/**
 * Interface para parsers
 */
interface ParserInterface
{
    /**
     * Parsea un archivo y retorna el AST
     */
    public function parseFile(string $filePath): array;

    /**
     * Parsea una cadena de código
     */
    public function parseString(string $code): array;
}
