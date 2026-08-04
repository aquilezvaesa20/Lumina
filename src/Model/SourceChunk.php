<?php

declare(strict_types=1);

namespace Lumina\Model;

/**
 * Value Object que representa un chunk de código fuente extraído.
 * 
 * Un SourceChunk es la unidad mínima de análisis en Lumina.
 * Puede ser una clase, interfaz, trait, método, función, constante, etc.
 */
class SourceChunk
{
    /**
     * @param int|null $id ID del chunk (null si es nuevo)
     * @param int $sourceId ID del archivo fuente
     * @param int $projectId ID del proyecto
     * @param string $chunkType Tipo de chunk (class, method, function, etc.)
     * @param string|null $name Nombre del elemento
     * @param string|null $parentName Nombre del elemento padre (si existe)
     * @param string|null $signature Firma completa del elemento
     * @param string $content Contenido del código
     * @param int $startLine Línea de inicio
     * @param int $endLine Línea de fin
     * @param string|null $visibility Visibilidad (public, protected, private)
     * @param bool $isStatic Si es estático
     * @param bool $isAbstract Si es abstracto
     * @param bool $isFinal Si es final
     * @param string|null $namespace Namespace del elemento
     * @param string|null $docblock DocBlock completo
     * @param string|null $returnType Tipo de retorno
     * @param array<string, mixed>|null $parameters Parámetros (si aplica)
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $sourceId,
        public readonly int $projectId,
        public readonly string $chunkType,
        public readonly ?string $name,
        public readonly ?string $parentName,
        public readonly ?string $signature,
        public readonly string $content,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly ?string $visibility = null,
        public readonly bool $isStatic = false,
        public readonly bool $isAbstract = false,
        public readonly bool $isFinal = false,
        public readonly ?string $namespace = null,
        public readonly ?string $docblock = null,
        public readonly ?string $returnType = null,
        public readonly ?array $parameters = null,
    ) {
    }

    /**
     * Obtiene el nombre cualificado completo (FQN) del chunk.
     * 
     * @return string El FQN (ej: App\Services\UserService::createUser)
     */
    public function getFqn(): string
    {
        $parts = [];
        if ($this->namespace !== null && $this->namespace !== '') {
            $parts[] = $this->namespace;
        }
        if ($this->parentName !== null && $this->parentName !== '') {
            $parts[] = $this->parentName;
        }
        if ($this->name !== null && $this->name !== '') {
            $parts[] = $this->name;
        }
        return implode('\\', $parts);
    }
}
