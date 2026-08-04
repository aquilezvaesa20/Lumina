<?php

declare(strict_types=1);

namespace Lumina\Contracts;

/**
 * Interface para populadores de relaciones
 */
interface PopulatorInterface
{
    /**
     * Analiza y popula las relaciones entre chunks
     */
    public function populate(array $chunks): array;
}
