<?php

declare(strict_types=1);

namespace Lumina\Dossier;

/**
 * Responde cada una de las 5 preguntas del dossier
 */
class QuestionAnswerer
{
    /**
     * ¿Dónde está? - Ubicación en el proyecto
     */
    public function whereIs(string $filePath, string $projectRoot): string
    {
        // TODO: Implementar en Fase 5
        return '';
    }

    /**
     * ¿Con qué interactúa? - Dependencias y relacionados
     */
    public function interactsWith(int $sourceId): array
    {
        // TODO: Implementar en Fase 5
        return [];
    }

    /**
     * ¿Qué hace? - Propósito principal
     */
    public function whatDoes(string $content): string
    {
        // TODO: Implementar en Fase 5
        return '';
    }

    /**
     * ¿Por qué existe? - Razón de ser
     */
    public function whyExists(string $content): ?string
    {
        // TODO: Implementar en Fase 5
        return null;
    }

    /**
     * ¿Cómo lo hace? - Mecanismo/estrategia
     */
    public function howDoes(string $content): string
    {
        // TODO: Implementar en Fase 5
        return '';
    }

    /**
     * Causas de fallo conocidas
     */
    public function failureCauses(string $content): ?string
    {
        // TODO: Implementar en Fase 5
        return null;
    }
}
