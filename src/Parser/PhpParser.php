<?php

declare(strict_types=1);

namespace Lumina\Parser;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;

/**
 * Wrapper de nikic/php-parser para analizar código PHP
 */
class PhpParser
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Parsea código PHP y retorna el AST
     *
     * @param string $code Código PHP a parsear
     * @return array{ast: Node\Stmt[]|null, errors: array}
     */
    public function parse(string $code): array
    {
        $errorHandler = new Collecting();
        
        try {
            $ast = $this->parser->parse($code, $errorHandler);
        } catch (\PhpParser\Error $e) {
            return [
                'ast' => null,
                'errors' => [$e->getMessage()],
            ];
        }

        return [
            'ast' => $ast,
            'errors' => $errorHandler->hasErrors() 
                ? array_map(fn($e) => $e->getMessage(), $errorHandler->getErrors())
                : [],
        ];
    }

    /**
     * Parsea un archivo PHP
     *
     * @param string $filePath Ruta al archivo PHP
     * @return array{ast: Node\Stmt[]|null, errors: array}
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['ast' => null, 'errors' => ["File not found: {$filePath}"]];
        }

        if (!is_readable($filePath)) {
            return ['ast' => null, 'errors' => ["File not readable: {$filePath}"]];
        }

        $code = file_get_contents($filePath);
        if ($code === false) {
            return ['ast' => null, 'errors' => ["Failed to read file: {$filePath}"]];
        }

        return $this->parse($code);
    }
}
