<?php

declare(strict_types=1);

namespace Lumina\Ai;

class PromptBuilder
{
    /**
     * Construye el prompt para enriquecer un dossier
     *
     * @param array<string, mixed> $source Información del archivo (ProjectSources)
     * @param array<int, array<string, mixed>> $chunks Chunks del archivo
     * @param array<int, array<string, mixed>> $relations Relaciones del archivo
     * @param array<string, mixed> $staticDossier Dossier estático actual
     * @return string Prompt completo
     */
    public function buildEnrichmentPrompt(
        array $source,
        array $chunks,
        array $relations,
        array $staticDossier
    ): string {
        $filename = $source['filename'] ?? 'desconocido';
        $code = $this->buildCodePreview($chunks);
        $contextInfo = $this->buildContextInfo($source, $chunks, $relations);

        return <<<PROMPT
Eres un experto en análisis de código PHP. Tu tarea es analizar el siguiente
archivo y generar un dossier técnico detallado respondiendo 5 preguntas
fundamentales.

## CONTEXTO DEL ARCHIVO

**Archivo:** {$filename}
**Ruta:** {$source['s3_key']}
**Tamaño:** {$source['size_bytes']} bytes

{$contextInfo}

## CÓDIGO DEL ARCHIVO

```php
{$code}
```

## TAREA

Responde las siguientes 5 preguntas en formato JSON estructurado. Sé preciso,
técnico y evita especulaciones. Basa tus respuestas ÚNICAMENTE en el código
proporcionado.

### Pregunta 1: ¿Dónde está?
Describe la ubicación del archivo en el proyecto, su namespace, tipo principal
y cómo se relaciona con la estructura general del proyecto.

### Pregunta 2: ¿Con qué interactúa?
Lista las dependencias principales del archivo (clases, funciones, librerías
externas) y cómo se conecta con otros componentes del sistema.

### Pregunta 3: ¿Qué hace?
Describe el propósito del archivo a nivel humano. ¿Qué problema resuelve?
¿Qué funcionalidad proporciona al proyecto?

### Pregunta 4: ¿Cómo lo hace?
Describe la implementación técnica: patrones de diseño utilizados, algoritmos,
estructura interna, flujos de datos principales.

### Pregunta 5: ¿Causa de fallo?
Identifica posibles puntos de fallo, edge cases, dependencias críticas,
problemas de rendimiento o seguridad que podrían causar errores.

## FORMATO DE RESPUESTA

Responde EXCLUSIVAMENTE con un objeto JSON válido (sin markdown, sin texto
adicional) con esta estructura exacta:

{
  "where_is": "Descripción de ubicación y contexto (2-3 oraciones)",
  "interacts_with": {
    "summary": "Resumen de interacciones (1 oración)",
    "dependencies": [
      {"name": "NombreClase", "type": "class|function|library", "usage": "cómo se usa"}
    ]
  },
  "what_does": "Descripción del propósito (2-3 oraciones claras)",
  "why_exists": "Razón de existir del archivo (1-2 oraciones)",
  "how_does": "Descripción técnica de implementación (3-5 oraciones)",
  "failure_causes": ["Causa 1", "Causa 2"],
  "confidence_score": 0.85,
  "key_insights": ["Insight 1", "Insight 2"]
}

Genera la respuesta JSON ahora:
PROMPT;
    }

    /**
     * Construye preview del código (limitado para no exceder tokens)
     *
     * @param array<int, array<string, mixed>> $chunks
     * @return string
     */
    private function buildCodePreview(array $chunks): string
    {
        $code = '';
        $maxLength = 8000; // ~2000 tokens
        
        foreach ($chunks as $chunk) {
            if ($chunk['chunk_type'] === 'import') {
                $code .= $chunk['content'] . "\n";
            }
        }
        
        foreach ($chunks as $chunk) {
            if (in_array($chunk['chunk_type'], ['class', 'interface', 'trait', 'function'])) {
                $code .= "\n" . $chunk['content'] . "\n";
            }
        }
        
        if (strlen($code) > $maxLength) {
            $code = substr($code, 0, $maxLength) . "\n// ... código truncado ...";
        }
        
        return $code;
    }

    /**
     * Construye información de contexto (relaciones, dependencias)
     *
     * @param array<string, mixed> $source
     * @param array<int, array<string, mixed>> $chunks
     * @param array<int, array<string, mixed>> $relations
     * @return string
     */
    private function buildContextInfo(array $source, array $chunks, array $relations): string
    {
        $info = [];
        
        // Namespace principal
        $namespace = '';
        foreach ($chunks as $chunk) {
            if (!empty($chunk['namespace'])) {
                $namespace = $chunk['namespace'];
                break;
            }
        }
        if ($namespace) {
            $info[] = "**Namespace:** `{$namespace}`";
        }
        
        // Chunks principales
        $mainChunks = array_filter($chunks, fn($c) => 
            in_array($c['chunk_type'], ['class', 'interface', 'trait', 'function'])
        );
        
        if (!empty($mainChunks)) {
            $info[] = "\n**Declaraciones principales:**";
            foreach (array_slice($mainChunks, 0, 5) as $chunk) {
                $info[] = "- {$chunk['chunk_type']}: `{$chunk['name']}`";
            }
        }
        
        // Relaciones conocidas
        if (!empty($relations)) {
            $info[] = "\n**Relaciones detectadas (análisis estático):**";
            $grouped = [];
            foreach ($relations as $rel) {
                $type = $rel['relation_type'];
                if (!isset($grouped[$type])) {
                    $grouped[$type] = [];
                }
                $grouped[$type][] = $rel['target_name'] ?? 'desconocido';
            }
            
            foreach ($grouped as $type => $targets) {
                $info[] = "- {$type}: " . implode(', ', array_slice($targets, 0, 3));
            }
        }
        
        return implode("\n", $info);
    }
}
