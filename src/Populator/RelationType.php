<?php

declare(strict_types=1);

namespace Lumina\Populator;

/**
 * Enum de tipos de relación entre chunks
 */
enum RelationType: string
{
    case EXTENDS = 'extends';
    case IMPLEMENTS = 'implements';
    case USES_TRAIT = 'uses_trait';
    case INSTANTIATES = 'instantiates';
    case STATIC_CALL = 'static_call';
    case METHOD_CALL = 'method_call';
    case PROPERTY_ACCESS = 'property_access';
    case EXTENDS_EXCEPTION = 'extends_exception';
    case THROWS = 'throws';
    case CATCHES = 'catches';
    case TYPE_HINT = 'type_hint';
    case RETURN_TYPE = 'return_type';
    case PARAM_TYPE = 'param_type';
    case DOCBLOCK_REF = 'docblock_ref';
    case ANNOTATION = 'annotation';
}
