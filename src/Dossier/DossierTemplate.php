<?php

declare(strict_types=1);

namespace Lumina\Dossier;

/**
 * Plantilla de las 5 preguntas del dossier
 */
class DossierTemplate
{
    /**
     * Retorna la plantilla base del dossier
     */
    public static function getTemplate(): string
    {
        return <<<'MD'
# Dossier: {filename}

## ¿Dónde está?
{where_is}

## ¿Con qué interactúa?
```json
{interacts_with}
```

## ¿Qué hace?
{what_does}

## ¿Por qué existe?
{why_exists}

## ¿Cómo lo hace?
{how_does}

## Causas de fallo conocidas
{failure_causes}

---
*Generado por Lumina v{version}*
MD;
    }

    /**
     * Renderiza el dossier con los datos proporcionados
     */
    public static function render(array $data): string
    {
        $template = self::getTemplate();

        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return $template;
    }
}
