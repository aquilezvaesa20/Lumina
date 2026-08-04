<?php

declare(strict_types=1);

namespace Lumina\Parser;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor que recorre el AST y extrae todos los chunks.
 */
class AstVisitor extends NodeVisitorAbstract
{
    private string $currentNamespace = '';
    private ?string $currentClass = null;
    private array $chunks = [];
    private NodeExtractor $extractor;

    public function __construct()
    {
        $this->extractor = new NodeExtractor();
    }

    public function enterNode(Node $node): ?int
    {
        // Namespace
        if ($node instanceof Stmt\Namespace_) {
            $this->currentNamespace = $node->name?->toString() ?? '';
            return null;
        }

        // Use statements (imports)
        if ($node instanceof Stmt\Use_) {
            $this->chunks[] = $this->extractor->extractImport($node, $this->currentNamespace);
            return null;
        }

        // Clase
        if ($node instanceof Stmt\Class_ && !$node->isAnonymous()) {
            $this->currentClass = $node->name?->toString();
            $this->chunks[] = $this->extractor->extractClass($node, $this->currentNamespace);
            return null;
        }

        // Interfaz
        if ($node instanceof Stmt\Interface_) {
            $this->currentClass = $node->name?->toString();
            $this->chunks[] = $this->extractor->extractInterface($node, $this->currentNamespace);
            return null;
        }

        // Trait
        if ($node instanceof Stmt\Trait_) {
            $this->currentClass = $node->name?->toString();
            $this->chunks[] = $this->extractor->extractTrait($node, $this->currentNamespace);
            return null;
        }

        // Enum
        if ($node instanceof Stmt\Enum_) {
            $this->currentClass = $node->name?->toString();
            $this->chunks[] = $this->extractor->extractEnum($node, $this->currentNamespace);
            return null;
        }

        // Función global
        if ($node instanceof Stmt\Function_ && $this->currentClass === null) {
            $this->chunks[] = $this->extractor->extractFunction($node, $this->currentNamespace);
            return null;
        }

        // Método de clase
        if ($node instanceof Stmt\ClassMethod && $this->currentClass !== null) {
            $this->chunks[] = $this->extractor->extractMethod(
                $node,
                $this->currentClass,
                $this->currentNamespace
            );
            return null;
        }

        // Propiedad de clase
        if ($node instanceof Stmt\Property && $this->currentClass !== null) {
            $this->chunks[] = $this->extractor->extractProperty(
                $node,
                $this->currentClass,
                $this->currentNamespace
            );
            return null;
        }

        // Constante de clase
        if ($node instanceof Stmt\ClassConst && $this->currentClass !== null) {
            $this->chunks[] = $this->extractor->extractClassConstant(
                $node,
                $this->currentClass,
                $this->currentNamespace
            );
            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): void
    {
        // Al salir de una clase/interfaz/trait/enum, resetear currentClass
        if ($node instanceof Stmt\Class_ || 
            $node instanceof Stmt\Interface_ || 
            $node instanceof Stmt\Trait_ || 
            $node instanceof Stmt\Enum_) {
            $this->currentClass = null;
        }
    }

    public function afterTraverse(array $nodes): void
    {
        // Resetear namespace al terminar
        $this->currentNamespace = '';
    }

    /**
     * Obtiene todos los chunks extraídos
     *
     * @return array<int, array<string, mixed>>
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    /**
     * Limpia los chunks para un nuevo archivo
     */
    public function reset(): void
    {
        $this->chunks = [];
        $this->currentNamespace = '';
        $this->currentClass = null;
    }
}
