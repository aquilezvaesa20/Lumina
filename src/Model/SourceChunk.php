<?php

declare(strict_types=1);

namespace Lumina\Model;

class SourceChunk
{
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
    ) {}

    public function getFqn(): string
    {
        $parts = [];
        if ($this->namespace) $parts[] = $this->namespace;
        if ($this->parentName) $parts[] = $this->parentName;
        if ($this->name) $parts[] = $this->name;
        return implode('\\', $parts);
    }
}
