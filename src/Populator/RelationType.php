<?php

declare(strict_types=1);

namespace Lumina\Populator;

/**
 * Enum con los tipos de relación entre chunks y métodos auxiliares.
 * 
 * Define todas las relaciones posibles que pueden existir entre SourceChunks
 * en el grafo de conocimiento de Lumina.
 */
enum RelationType: string
{
    case CALLS = 'calls';
    case IMPORTS = 'imports';
    case EXTENDS = 'extends';
    case IMPLEMENTS = 'implements';
    case USES_TRAIT = 'uses_trait';
    case INSTANTIATES = 'instantiates';
    case TYPE_HINTS = 'type_hints';
    case RETURNS = 'returns';
    case THROWS = 'throws';
    case CONTAINS = 'contains';
    case REFERENCES = 'references';
    case OVERRIDES = 'overrides';

    /**
     * Obtiene la descripción humana del tipo de relación
     *
     * @return string Descripción legible de la relación
     */
    public function description(): string
    {
        return match($this) {
            self::CALLS => 'Llama a',
            self::IMPORTS => 'Importa',
            self::EXTENDS => 'Extiende',
            self::IMPLEMENTS => 'Implementa',
            self::USES_TRAIT => 'Usa trait',
            self::INSTANTIATES => 'Instancia',
            self::TYPE_HINTS => 'Usa como type hint',
            self::RETURNS => 'Retorna tipo',
            self::THROWS => 'Lanza excepción',
            self::CONTAINS => 'Contiene',
            self::REFERENCES => 'Referencia',
            self::OVERRIDES => 'Sobrescribe',
        };
    }

    /**
     * Indica si la relación es direccional (A → B)
     *
     * @return bool true si la relación tiene dirección, false si es bidireccional
     */
    public function isDirectional(): bool
    {
        return true; // Todas las relaciones son direccionales
    }
}
