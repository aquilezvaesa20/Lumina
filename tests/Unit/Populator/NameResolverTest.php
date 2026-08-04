<?php

declare(strict_types=1);

namespace Lumina\Tests\Unit\Populator;

use Lumina\Populator\NameResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para NameResolver
 */
class NameResolverTest extends TestCase
{
    private NameResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new NameResolver();
    }

    public function testResolveFqn(): void
    {
        $this->resolver->loadImports([], '');
        $this->assertEquals('App\\Models\\User', $this->resolver->resolve('\\App\\Models\\User'));
    }

    public function testResolveWithUseAlias(): void
    {
        $imports = [
            ['meta' => json_encode(['uses' => [['name' => 'App\\Models\\User', 'alias' => 'User']]])]
        ];
        $this->resolver->loadImports($imports, 'App\\Controllers');
        
        $this->assertEquals('App\\Models\\User', $this->resolver->resolve('User'));
    }

    public function testResolveRelativeName(): void
    {
        $this->resolver->loadImports([], 'App\\Services');
        $this->assertEquals('App\\Services\\AuthService', $this->resolver->resolve('AuthService'));
    }

    public function testResolveGlobalFunction(): void
    {
        $this->resolver->loadImports([], '');
        $this->assertEquals('array_map', $this->resolver->resolve('array_map'));
    }

    public function testGetShortName(): void
    {
        $this->assertEquals('User', $this->resolver->getShortName('App\\Models\\User'));
        $this->assertEquals('array_map', $this->resolver->getShortName('array_map'));
    }

    public function testGetNamespace(): void
    {
        $this->assertEquals('App\\Models', $this->resolver->getNamespace('App\\Models\\User'));
        $this->assertEquals('', $this->resolver->getNamespace('User'));
    }
}
