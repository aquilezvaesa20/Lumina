<?php

declare(strict_types=1);

namespace Lumina\Tests\Unit\Populator;

use Lumina\Populator\RelationType;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para RelationType
 */
class RelationTypeTest extends TestCase
{
    public function testDescriptions(): void
    {
        $this->assertEquals('Llama a', RelationType::CALLS->description());
        $this->assertEquals('Extiende', RelationType::EXTENDS->description());
        $this->assertEquals('Implementa', RelationType::IMPLEMENTS->description());
    }

    public function testValues(): void
    {
        $this->assertEquals('calls', RelationType::CALLS->value);
        $this->assertEquals('extends', RelationType::EXTENDS->value);
        $this->assertEquals('implements', RelationType::IMPLEMENTS->value);
    }
}
