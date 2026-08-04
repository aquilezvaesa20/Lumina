<?php

declare(strict_types=1);

namespace Lumina\Populator;

/**
 * Construye el grafo de dependencias
 */
class DependencyGraph
{
    /**
     * @var array<string, array<int>>
     */
    private array $adjacencyList = [];

    /**
     * Agrega una arista al grafo
     */
    public function addEdge(int $sourceId, int $targetId): void
    {
        // TODO: Implementar en Fase 4
    }

    /**
     * Obtiene todas las dependencias de un nodo
     */
    public function getDependencies(int $nodeId): array
    {
        // TODO: Implementar en Fase 4
        return [];
    }

    /**
     * Obtiene todos los nodos que dependen de este
     */
    public function getDependents(int $nodeId): array
    {
        // TODO: Implementar en Fase 4
        return [];
    }
}
