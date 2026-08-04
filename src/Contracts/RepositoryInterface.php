<?php

declare(strict_types=1);

namespace Lumina\Contracts;

/**
 * Interface para todos los repositorios
 */
interface RepositoryInterface
{
    /**
     * Encuentra un registro por ID
     */
    public function find(int $id): ?array;

    /**
     * Encuentra todos los registros
     */
    public function findAll(): array;

    /**
     * Crea un nuevo registro
     */
    public function create(array $data): int;

    /**
     * Actualiza un registro existente
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un registro
     */
    public function delete(int $id): bool;
}
