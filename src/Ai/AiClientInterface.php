<?php

declare(strict_types=1);

namespace Lumina\Ai;

interface AiClientInterface
{
    /**
     * Envía un prompt y recibe respuesta estructurada
     *
     * @param string $prompt El prompt completo
     * @param array<string, mixed> $options Opciones adicionales (temperature, max_tokens, etc.)
     * @return array{content: string, usage: array<string, int>, model: string}
     */
    public function complete(string $prompt, array $options = []): array;

    /**
     * Verifica si el cliente está configurado correctamente
     */
    public function isConfigured(): bool;

    /**
     * Obtiene el nombre del modelo/proveedor
     */
    public function getProviderName(): string;
}
