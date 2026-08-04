<?php

declare(strict_types=1);

namespace Lumina\Parser;

use PhpParser\NodeVisitorAbstract;

/**
 * Visitor pattern para recorrer el AST
 */
class AstVisitor extends NodeVisitorAbstract
{
    /**
     * @var SourceChunk[]
     */
    private array $chunks = [];

    public function getChunks(): array
    {
        return $this->chunks;
    }

    // TODO: Implementar métodos del visitor en Fase 3
}
