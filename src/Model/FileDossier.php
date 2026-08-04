<?php

declare(strict_types=1);

namespace Lumina\Model;

/**
 * Value Object que representa un dossier de comprensión de un archivo.
 * 
 * El dossier es un documento estructurado que responde las preguntas clave:
 * - ¿Dónde está? (whereIs)
 * - ¿Con qué interactúa? (interactsWith)
 * - ¿Qué hace? (whatDoes)
 * - ¿Por qué existe? (whyExists)
 * - ¿Cómo lo hace? (howDoes)
 * - Causas de fallo conocidas (failureCauses)
 */
class FileDossier
{
    /**
     * @param int $sourceId ID del archivo fuente
     * @param int $projectId ID del proyecto
     * @param string $whereIs Ubicación y propósito general del archivo
     * @param array<int, array<string, mixed>>|null $interactsWith Lista de elementos con los que interactúa
     * @param string $whatDoes Descripción de lo que hace el archivo
     * @param string|null $whyExists Justificación de la existencia del archivo
     * @param string $howDoes Explicación de cómo implementa su funcionalidad
     * @param string|null $failureCauses Causas conocidas de fallo
     * @param bool $aiGenerated Si fue generado por IA
     * @param float|null $confidenceScore Puntuación de confianza (0-1)
     */
    public function __construct(
        public readonly int $sourceId,
        public readonly int $projectId,
        public readonly string $whereIs,
        public readonly ?array $interactsWith,
        public readonly string $whatDoes,
        public readonly ?string $whyExists,
        public readonly string $howDoes,
        public readonly ?string $failureCauses,
        public readonly bool $aiGenerated = false,
        public readonly ?float $confidenceScore = null,
    ) {
    }

    /**
     * Convierte el dossier a formato Markdown.
     * 
     * @return string El dossier en formato Markdown
     */
    public function toMarkdown(): string
    {
        return <<<MD
        # Dossier: Archivo #{$this->sourceId}

        ## ¿Dónde está?
        {$this->whereIs}

        ## ¿Con qué interactúa?
        ```json
        {$this->interactsWithJson()}
        ```

        ## ¿Qué hace?
        {$this->whatDoes}

        ## ¿Por qué existe?
        {$this->whyExists ?? 'No disponible'}

        ## ¿Cómo lo hace?
        {$this->howDoes}

        ## Causas de fallo conocidas
        {$this->failureCauses ?? 'No documentadas'}

        ---
        *Generado por Lumina | Confianza: {$this->getConfidenceString()}*
        MD;
    }

    /**
     * Obtiene la lista de interacciones como JSON formateado.
     * 
     * @return string JSON con las interacciones
     */
    private function interactsWithJson(): string
    {
        if ($this->interactsWith === null || $this->interactsWith === []) {
            return '[]';
        }
        return json_encode($this->interactsWith, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Obtiene la puntuación de confianza como string legible.
     * 
     * @return string La confianza formateada o "N/A"
     */
    private function getConfidenceString(): string
    {
        if ($this->confidenceScore === null) {
            return 'N/A';
        }
        return number_format($this->confidenceScore * 100, 1) . '%';
    }
}
