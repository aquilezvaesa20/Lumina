<?php

declare(strict_types=1);

namespace Lumina\Model;

class ChunkRelation
{
    public function __construct(
        public readonly int $sourceChunkId,
        public readonly int $targetChunkId,
        public readonly int $projectId,
        public readonly string $relationType,
        public readonly ?string $context = null,
        public readonly ?int $contextLine = null,
        public readonly bool $isConfirmed = true,
    ) {}
}
