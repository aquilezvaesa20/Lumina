<?php

declare(strict_types=1);

namespace Lumina\Model;

class Project
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $path,
        public readonly ?string $description = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}
}
