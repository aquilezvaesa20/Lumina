<?php

declare(strict_types=1);

namespace Lumina\Model;

class ProjectSource
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $projectId,
        public readonly string $filePath,
        public readonly string $relativePath,
        public readonly int $fileSize,
        public readonly string $fileHash,
        public readonly ?string $lastAnalyzedAt = null,
        public readonly ?int $chunkCount = null,
    ) {}
}
