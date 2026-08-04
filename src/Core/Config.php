<?php

declare(strict_types=1);

namespace Lumina\Core;

/**
 * Gestor de configuración de Lumina
 */
class Config
{
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function load(?string $configPath = null): self
    {
        $path = $configPath ?? __DIR__ . '/../../config/lumina.php';

        if (!file_exists($path)) {
            throw new \RuntimeException("Archivo de configuración no encontrado: {$path}");
        }

        return new self(require $path);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function getDatabaseConfig(): array
    {
        return $this->get('database', []);
    }

    public function getParserConfig(): array
    {
        return $this->get('parser', []);
    }
}
