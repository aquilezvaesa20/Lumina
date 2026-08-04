<?php

declare(strict_types=1);

namespace Lumina\Populator;

/**
 * Resuelve nombres de clases/funciones a su FQN (Fully Qualified Name) usando
 * el namespace actual y los use statements del archivo.
 */
class NameResolver
{
    /** @var array<string, string> Mapa de alias → FQN */
    private array $useMap = [];
    
    private string $currentNamespace = '';

    /**
     * Carga los imports de un archivo para resolución de nombres
     *
     * @param array $imports Chunks tipo 'import' del archivo
     * @param string $namespace Namespace del archivo
     */
    public function loadImports(array $imports, string $namespace): void
    {
        $this->useMap = [];
        $this->currentNamespace = $namespace;

        foreach ($imports as $import) {
            $meta = json_decode($import['meta'] ?? '{}', true);
            if (!isset($meta['uses'])) continue;

            foreach ($meta['uses'] as $use) {
                $alias = $use['alias'] ?? $this->getShortName($use['name']);
                $this->useMap[$alias] = $use['name'];
            }
        }
    }

    /**
     * Resuelve un nombre a FQN
     *
     * @param string $name Nombre a resolver (puede ser corto, relativo o FQN)
     * @return string FQN resuelto
     */
    public function resolve(string $name): string
    {
        // Ya es FQN (empieza con \)
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }

        // Verificar si es un alias de use
        $parts = explode('\\', $name);
        $first = $parts[0];

        if (isset($this->useMap[$first])) {
            $parts[0] = $this->useMap[$first];
            return implode('\\', $parts);
        }

        // Si no hay namespace, es global
        if (empty($this->currentNamespace)) {
            return $name;
        }

        // Nombre relativo al namespace actual
        return $this->currentNamespace . '\\' . $name;
    }

    /**
     * Obtiene el nombre corto de un FQN
     *
     * @param string $fqn FQN completo
     * @return string Nombre corto (última parte del FQN)
     */
    public function getShortName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return end($parts);
    }

    /**
     * Obtiene el namespace de un FQN
     *
     * @param string $fqn FQN completo
     * @return string Namespace (todas las partes menos la última)
     */
    public function getNamespace(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        array_pop($parts);
        return implode('\\', $parts);
    }

    /**
     * Resetea el resolver para un nuevo archivo
     */
    public function reset(): void
    {
        $this->useMap = [];
        $this->currentNamespace = '';
    }
}
