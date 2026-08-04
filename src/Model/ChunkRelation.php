<?php

declare(strict_types=1);

namespace Lumina\Model;

/**
 * Value Object que representa una relación entre dos chunks de código.
 * 
 * Las relaciones pueden ser de varios tipos: extends, implements, uses,
 * calls, instantiates, typeHint, etc.
 */
class ChunkRelation
{
    /**
     * @param int $sourceChunkId ID del chunk origen
     * @param int $targetChunkId ID del chunk destino
     * @param int $projectId ID del proyecto
     * @param string $relationType Tipo de relación (extends, implements, uses, calls, etc.)
     * @param string|null $context Contexto adicional de la relación
     * @param int|null $contextLine Línea donde ocurre la relación
     * @param bool $isConfirmed Si la relación está confirmada (true) o es inferida (false)
     */
    public function __construct(
        public readonly int $sourceChunkId,
        public readonly int $targetChunkId,
        public readonly int $projectId,
        public readonly string $relationType,
        public readonly ?string $context = null,
        public readonly ?int $contextLine = null,
        public readonly bool $isConfirmed = true,
    ) {
    }
}
