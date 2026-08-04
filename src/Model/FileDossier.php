<?php

declare(strict_types=1);

namespace Lumina\Model;

class FileDossier
{
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
    ) {}

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
        {$this->whyExists}

        ## ¿Cómo lo hace?
        {$this->howDoes}

        ## Causas de fallo conocidas
        {$this->failureCauses}

        ---
        *Generado por Lumina | Confianza: {$this->confidenceScore}*
        MD;
    }

    private function interactsWithJson(): string
    {
        return $this->interactsWith
            ? json_encode($this->interactsWith, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '[]';
    }
}
