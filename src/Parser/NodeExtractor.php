<?php

declare(strict_types=1);

namespace Lumina\Parser;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\PrettyPrinter\Standard;

/**
 * Extrae información de nodos AST y la convierte en arrays listos para BD.
 */
class NodeExtractor
{
    private Standard $printer;

    public function __construct()
    {
        $this->printer = new Standard();
    }

    /**
     * Extrae información de un nodo de clase
     */
    public function extractClass(Stmt\Class_ $node, string $namespace): array
    {
        return [
            'chunk_type' => 'class',
            'name' => $node->name?->toString(),
            'parent_name' => null,
            'namespace' => $namespace,
            'signature' => $this->buildClassSignature($node),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => null,
            'is_static' => false,
            'is_abstract' => $node->isAbstract(),
            'is_final' => $node->isFinal(),
            'docblock' => $this->getDocblock($node),
            'return_type' => null,
            'parameters_json' => null,
            'meta' => json_encode([
                'extends' => $node->extends?->toString(),
                'implements' => array_map(fn($i) => $i->toString(), $node->implements),
                'is_anonymous' => $node->isAnonymous(),
            ]),
        ];
    }

    /**
     * Extrae información de un nodo de interfaz
     */
    public function extractInterface(Stmt\Interface_ $node, string $namespace): array
    {
        return [
            'chunk_type' => 'interface',
            'name' => $node->name?->toString(),
            'parent_name' => null,
            'namespace' => $namespace,
            'signature' => $this->buildInterfaceSignature($node),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => null,
            'is_static' => false,
            'is_abstract' => true,
            'is_final' => false,
            'docblock' => $this->getDocblock($node),
            'return_type' => null,
            'parameters_json' => null,
            'meta' => json_encode([
                'extends' => array_map(fn($i) => $i->toString(), $node->extends),
            ]),
        ];
    }

    /**
     * Extrae información de un nodo de trait
     */
    public function extractTrait(Stmt\Trait_ $node, string $namespace): array
    {
        return [
            'chunk_type' => 'trait',
            'name' => $node->name?->toString(),
            'parent_name' => null,
            'namespace' => $namespace,
            'signature' => "trait {$node->name}",
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => null,
            'is_static' => false,
            'is_abstract' => false,
            'is_final' => false,
            'docblock' => $this->getDocblock($node),
            'return_type' => null,
            'parameters_json' => null,
            'meta' => null,
        ];
    }

    /**
     * Extrae información de un nodo de función
     */
    public function extractFunction(Stmt\Function_ $node, string $namespace): array
    {
        return [
            'chunk_type' => 'function',
            'name' => $node->name?->toString(),
            'parent_name' => null,
            'namespace' => $namespace,
            'signature' => $this->buildFunctionSignature($node),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => 'global',
            'is_static' => false,
            'is_abstract' => false,
            'is_final' => false,
            'docblock' => $this->getDocblock($node),
            'return_type' => $this->getTypeAsString($node->returnType),
            'parameters_json' => json_encode($this->extractParameters($node->params)),
            'meta' => json_encode([
                'returns_by_ref' => $node->byRef,
            ]),
        ];
    }

    /**
     * Extrae información de un nodo de método
     */
    public function extractMethod(Stmt\ClassMethod $node, string $className, string $namespace): array
    {
        return [
            'chunk_type' => 'method',
            'name' => $node->name?->toString(),
            'parent_name' => $className,
            'namespace' => $namespace,
            'signature' => $this->buildMethodSignature($node),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => $this->getVisibility($node),
            'is_static' => $node->isStatic(),
            'is_abstract' => $node->isAbstract(),
            'is_final' => $node->isFinal(),
            'docblock' => $this->getDocblock($node),
            'return_type' => $this->getTypeAsString($node->returnType),
            'parameters_json' => json_encode($this->extractParameters($node->params)),
            'meta' => json_encode([
                'is_magic' => str_starts_with($node->name->toString(), '__'),
                'returns_by_ref' => $node->byRef,
            ]),
        ];
    }

    /**
     * Extrae información de un nodo de propiedad
     */
    public function extractProperty(Stmt\Property $node, string $className, string $namespace): array
    {
        $prop = $node->props[0] ?? null;
        
        return [
            'chunk_type' => 'property',
            'name' => $prop?->name?->toString(),
            'parent_name' => $className,
            'namespace' => $namespace,
            'signature' => $this->buildPropertySignature($node),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => $this->getVisibility($node),
            'is_static' => $node->isStatic(),
            'is_abstract' => false,
            'is_final' => false,
            'docblock' => $this->getDocblock($node),
            'return_type' => $this->getTypeAsString($node->type),
            'parameters_json' => null,
            'meta' => json_encode([
                'is_readonly' => $node->isReadonly(),
                'has_default' => $prop?->default !== null,
            ]),
        ];
    }

    /**
     * Extrae información de un nodo de constante de clase
     */
    public function extractClassConstant(Stmt\ClassConst $node, string $className, string $namespace): array
    {
        $const = $node->consts[0] ?? null;
        
        return [
            'chunk_type' => 'constant',
            'name' => $const?->name?->toString(),
            'parent_name' => $className,
            'namespace' => $namespace,
            'signature' => $this->buildConstantSignature($node, $const),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => $this->getVisibility($node),
            'is_static' => true,
            'is_abstract' => false,
            'is_final' => $node->isFinal(),
            'docblock' => $this->getDocblock($node),
            'return_type' => null,
            'parameters_json' => null,
            'meta' => null,
        ];
    }

    /**
     * Extrae información de un nodo de enum
     */
    public function extractEnum(Stmt\Enum_ $node, string $namespace): array
    {
        return [
            'chunk_type' => 'class',
            'name' => $node->name?->toString(),
            'parent_name' => null,
            'namespace' => $namespace,
            'signature' => $this->buildEnumSignature($node),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => null,
            'is_static' => false,
            'is_abstract' => false,
            'is_final' => false,
            'docblock' => $this->getDocblock($node),
            'return_type' => null,
            'parameters_json' => null,
            'meta' => json_encode([
                'is_enum' => true,
                'backed_type' => $node->scalarType?->toString(),
                'implements' => array_map(fn($i) => $i->toString(), $node->implements),
            ]),
        ];
    }

    /**
     * Extrae información de un use statement (import)
     */
    public function extractImport(Stmt\Use_ $node, string $namespace): array
    {
        $uses = [];
        foreach ($node->uses as $use) {
            $uses[] = [
                'name' => $use->name->toString(),
                'alias' => $use->alias?->toString(),
                'type' => $use->type,
            ];
        }

        return [
            'chunk_type' => 'import',
            'name' => null,
            'parent_name' => null,
            'namespace' => $namespace,
            'signature' => $this->printer->prettyPrint([$node]),
            'content' => $this->printer->prettyPrint([$node]),
            'start_line' => $node->getStartLine(),
            'end_line' => $node->getEndLine(),
            'visibility' => null,
            'is_static' => false,
            'is_abstract' => false,
            'is_final' => false,
            'docblock' => null,
            'return_type' => null,
            'parameters_json' => null,
            'meta' => json_encode(['uses' => $uses]),
        ];
    }

    // ==========================================
    // MÉTODOS PRIVADOS AUXILIARES
    // ==========================================

    private function buildClassSignature(Stmt\Class_ $node): string
    {
        $parts = [];
        
        if ($node->isAbstract()) $parts[] = 'abstract';
        if ($node->isFinal()) $parts[] = 'final';
        $parts[] = 'class';
        $parts[] = $node->name?->toString() ?? 'anonymous';
        
        if ($node->extends) {
            $parts[] = 'extends';
            $parts[] = $node->extends->toString();
        }
        
        if (!empty($node->implements)) {
            $parts[] = 'implements';
            $parts[] = implode(', ', array_map(fn($i) => $i->toString(), $node->implements));
        }
        
        return implode(' ', $parts);
    }

    private function buildInterfaceSignature(Stmt\Interface_ $node): string
    {
        $parts = ['interface', $node->name?->toString()];
        
        if (!empty($node->extends)) {
            $parts[] = 'extends';
            $parts[] = implode(', ', array_map(fn($i) => $i->toString(), $node->extends));
        }
        
        return implode(' ', $parts);
    }

    private function buildEnumSignature(Stmt\Enum_ $node): string
    {
        $parts = ['enum', $node->name?->toString()];
        
        if ($node->scalarType) {
            $parts[] = ':' . $node->scalarType->toString();
        }
        
        if (!empty($node->implements)) {
            $parts[] = 'implements';
            $parts[] = implode(', ', array_map(fn($i) => $i->toString(), $node->implements));
        }
        
        return implode(' ', $parts);
    }

    private function buildFunctionSignature(Stmt\Function_ $node): string
    {
        $params = $this->buildParametersSignature($node->params);
        $return = $node->returnType ? ': ' . $this->getTypeAsString($node->returnType) : '';
        
        return "function {$node->name}({$params}){$return}";
    }

    private function buildMethodSignature(Stmt\ClassMethod $node): string
    {
        $parts = [];
        
        $visibility = $this->getVisibility($node);
        if ($visibility && $visibility !== 'global') $parts[] = $visibility;
        if ($node->isStatic()) $parts[] = 'static';
        if ($node->isAbstract()) $parts[] = 'abstract';
        if ($node->isFinal()) $parts[] = 'final';
        
        $parts[] = 'function';
        $parts[] = $node->name->toString();
        
        $params = $this->buildParametersSignature($node->params);
        $return = $node->returnType ? ': ' . $this->getTypeAsString($node->returnType) : '';
        
        return implode(' ', $parts) . "({$params}){$return}";
    }

    private function buildPropertySignature(Stmt\Property $node): string
    {
        $parts = [];
        
        $visibility = $this->getVisibility($node);
        if ($visibility) $parts[] = $visibility;
        if ($node->isStatic()) $parts[] = 'static';
        if ($node->isReadonly()) $parts[] = 'readonly';
        
        $type = $node->type ? $this->getTypeAsString($node->type) : 'mixed';
        $parts[] = $type;
        
        $prop = $node->props[0] ?? null;
        $parts[] = '$' . ($prop?->name?->toString() ?? 'unknown');
        
        return implode(' ', $parts);
    }

    private function buildConstantSignature(Stmt\ClassConst $node, ?Node\Const_ $const): string
    {
        $parts = [];
        
        $visibility = $this->getVisibility($node);
        if ($visibility) $parts[] = $visibility;
        if ($node->isFinal()) $parts[] = 'final';
        
        $parts[] = 'const';
        $parts[] = $const?->name?->toString() ?? 'UNKNOWN';
        
        return implode(' ', $parts);
    }

    private function buildParametersSignature(array $params): string
    {
        $parts = [];
        
        foreach ($params as $param) {
            $part = '';
            
            if ($param->type) {
                $part .= $this->getTypeAsString($param->type) . ' ';
            }
            
            if ($param->variadic) {
                $part .= '...';
            }
            
            if ($param->byRef) {
                $part .= '&';
            }
            
            $part .= '$' . $param->var->name;
            
            if ($param->default) {
                $part .= ' = ' . $this->printer->prettyPrintExpr($param->default);
            }
            
            $parts[] = $part;
        }
        
        return implode(', ', $parts);
    }

    private function extractParameters(array $params): array
    {
        $result = [];
        
        foreach ($params as $param) {
            $result[] = [
                'name' => '$' . $param->var->name,
                'type' => $param->type ? $this->getTypeAsString($param->type) : null,
                'default' => $param->default ? $this->printer->prettyPrintExpr($param->default) : null,
                'is_variadic' => $param->variadic,
                'is_by_ref' => $param->byRef,
            ];
        }
        
        return $result;
    }

    private function getTypeAsString(?Node $type): ?string
    {
        if ($type === null) {
            return null;
        }
        
        if ($type instanceof Node\Identifier) {
            return $type->toString();
        }
        
        if ($type instanceof Node\Name) {
            return $type->toString();
        }
        
        if ($type instanceof Node\NullableType) {
            return '?' . $this->getTypeAsString($type->type);
        }
        
        if ($type instanceof Node\UnionType) {
            return implode('|', array_map(fn($t) => $this->getTypeAsString($t), $type->types));
        }
        
        if ($type instanceof Node\IntersectionType) {
            return implode('&', array_map(fn($t) => $this->getTypeAsString($t), $type->types));
        }
        
        return $this->printer->prettyPrint([$type]);
    }

    private function getVisibility(Node $node): ?string
    {
        if (method_exists($node, 'isPublic') && $node->isPublic()) {
            return 'public';
        }
        
        if (method_exists($node, 'isProtected') && $node->isProtected()) {
            return 'protected';
        }
        
        if (method_exists($node, 'isPrivate') && $node->isPrivate()) {
            return 'private';
        }
        
        return null;
    }

    private function getDocblock(Node $node): ?string
    {
        $docComment = $node->getDocComment();
        return $docComment?->getText();
    }
}
