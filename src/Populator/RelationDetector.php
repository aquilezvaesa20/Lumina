<?php

declare(strict_types=1);

namespace Lumina\Populator;

use Lumina\Parser\PhpParser;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Analiza el contenido de un chunk para detectar referencias a otros chunks.
 * 
 * Usa el AST de nikic/php-parser para encontrar llamadas, instancias, type hints, etc.
 * y construir relaciones entre los diferentes elementos del código.
 */
class RelationDetector
{
    private PhpParser $parser;
    private NameResolver $nameResolver;

    public function __construct()
    {
        $this->parser = new PhpParser();
        $this->nameResolver = new NameResolver();
    }

    /**
     * Detecta todas las relaciones de un chunk
     *
     * @param array $chunk El chunk a analizar (de SourceChunks)
     * @param array $fileImports Imports del archivo del chunk
     * @param string $fileNamespace Namespace del archivo
     * @return array Lista de relaciones detectadas
     */
    public function detectRelations(
        array $chunk,
        array $fileImports,
        string $fileNamespace
    ): array {
        $this->nameResolver->loadImports($fileImports, $fileNamespace);

        // Parsear el contenido del chunk
        $code = "<?php\n" . $chunk['content'];
        $result = $this->parser->parse($code);

        if ($result['ast'] === null) {
            return [];
        }

        $visitor = new class($this->nameResolver, $chunk) extends NodeVisitorAbstract {
            private array $found = [];

            public function __construct(
                private NameResolver $resolver,
                private array $chunk
            ) {}

            public function enterNode(Node $node): ?int
            {
                // Extends
                if ($node instanceof Stmt\Class_ && $node->extends) {
                    $this->found[] = [
                        'target_name' => $node->extends->toString(),
                        'relation_type' => RelationType::EXTENDS,
                        'context' => 'extends ' . $node->extends->toString(),
                        'context_line' => $node->extends->getStartLine(),
                    ];
                }

                // Implements
                if ($node instanceof Stmt\Class_ && !empty($node->implements)) {
                    foreach ($node->implements as $interface) {
                        $this->found[] = [
                            'target_name' => $interface->toString(),
                            'relation_type' => RelationType::IMPLEMENTS,
                            'context' => 'implements ' . $interface->toString(),
                            'context_line' => $interface->getStartLine(),
                        ];
                    }
                }

                // Use trait
                if ($node instanceof Stmt\TraitUse) {
                    foreach ($node->traits as $trait) {
                        $this->found[] = [
                            'target_name' => $trait->toString(),
                            'relation_type' => RelationType::USES_TRAIT,
                            'context' => 'use ' . $trait->toString(),
                            'context_line' => $trait->getStartLine(),
                        ];
                    }
                }

                // new ClassName()
                if ($node instanceof Expr\New_ && $node->class instanceof Node\Name) {
                    $this->found[] = [
                        'target_name' => $node->class->toString(),
                        'relation_type' => RelationType::INSTANTIATES,
                        'context' => 'new ' . $node->class->toString(),
                        'context_line' => $node->getStartLine(),
                    ];
                }

                // Llamadas a métodos: $obj->method() o ClassName::method()
                if ($node instanceof Expr\MethodCall) {
                    // No podemos resolver el tipo del objeto sin análisis más profundo
                    // Pero podemos registrar el nombre del método
                    if ($node->name instanceof Node\Identifier) {
                        $this->found[] = [
                            'target_name' => $node->name->toString(),
                            'relation_type' => RelationType::CALLS,
                            'context' => '->' . $node->name->toString() . '()',
                            'context_line' => $node->getStartLine(),
                            'is_confirmed' => false, // Inferida, no confirmada
                        ];
                    }
                }

                // Llamadas estáticas: ClassName::method()
                if ($node instanceof Expr\StaticCall && $node->class instanceof Node\Name) {
                    $this->found[] = [
                        'target_name' => $node->class->toString(),
                        'relation_type' => RelationType::CALLS,
                        'context' => $node->class->toString() . '::' . 
                            ($node->name instanceof Node\Identifier ? $node->name->toString() : '?'),
                        'context_line' => $node->getStartLine(),
                    ];
                }

                // Llamadas a funciones: functionName()
                if ($node instanceof Expr\FuncCall && $node->name instanceof Node\Name) {
                    $this->found[] = [
                        'target_name' => $node->name->toString(),
                        'relation_type' => RelationType::CALLS,
                        'context' => $node->name->toString() . '()',
                        'context_line' => $node->getStartLine(),
                    ];
                }

                // Type hints en parámetros
                if ($node instanceof Node\Param && $node->type instanceof Node\Name) {
                    $this->found[] = [
                        'target_name' => $node->type->toString(),
                        'relation_type' => RelationType::TYPE_HINTS,
                        'context' => 'param: ' . $node->type->toString(),
                        'context_line' => $node->getStartLine(),
                    ];
                }

                // Return type
                if (($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassMethod) 
                    && $node->returnType instanceof Node\Name) {
                    $this->found[] = [
                        'target_name' => $node->returnType->toString(),
                        'relation_type' => RelationType::RETURNS,
                        'context' => 'returns: ' . $node->returnType->toString(),
                        'context_line' => $node->returnType->getStartLine(),
                    ];
                }

                // Throw
                if ($node instanceof Expr\Throw_ && $node->expr instanceof Expr\New_) {
                    if ($node->expr->class instanceof Node\Name) {
                        $this->found[] = [
                            'target_name' => $node->expr->class->toString(),
                            'relation_type' => RelationType::THROWS,
                            'context' => 'throw new ' . $node->expr->class->toString(),
                            'context_line' => $node->getStartLine(),
                        ];
                    }
                }

                return null;
            }

            public function getFound(): array
            {
                return $this->found;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($result['ast']);

        // Resolver nombres y construir relaciones
        $detectedRelations = [];
        foreach ($visitor->getFound() as $found) {
            $fqn = $this->nameResolver->resolve($found['target_name']);
            
            $detectedRelations[] = [
                'target_fqn' => $fqn,
                'target_short_name' => $this->nameResolver->getShortName($fqn),
                'relation_type' => $found['relation_type'],
                'context' => $found['context'] ?? null,
                'context_line' => $found['context_line'] ?? null,
                'is_confirmed' => $found['is_confirmed'] ?? true,
            ];
        }

        return $detectedRelations;
    }
}
