<?php

declare(strict_types=1);

namespace Lumina\Tests\Unit\Parser;

use Lumina\Parser\NodeExtractor;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para NodeExtractor
 */
class NodeExtractorTest extends TestCase
{
    private NodeExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new NodeExtractor();
    }

    public function testExtractClassBasic(): void
    {
        $class = new Class_(new Identifier('TestClass'));
        $result = $this->extractor->extractClass($class, 'App\\Models');

        $this->assertEquals('class', $result['chunk_type']);
        $this->assertEquals('TestClass', $result['name']);
        $this->assertEquals('App\\Models', $result['namespace']);
        $this->assertFalse($result['is_abstract']);
        $this->assertFalse($result['is_final']);
    }

    public function testExtractClassAbstract(): void
    {
        $class = new Class_(
            new Identifier('AbstractClass'),
            ['flags' => Class_::MODIFIER_ABSTRACT]
        );
        $result = $this->extractor->extractClass($class, '');

        $this->assertTrue($result['is_abstract']);
        $this->assertFalse($result['is_final']);
    }

    public function testExtractClassFinal(): void
    {
        $class = new Class_(
            new Identifier('FinalClass'),
            ['flags' => Class_::MODIFIER_FINAL]
        );
        $result = $this->extractor->extractClass($class, '');

        $this->assertTrue($result['is_final']);
        $this->assertFalse($result['is_abstract']);
    }

    public function testExtractClassWithExtends(): void
    {
        $class = new Class_(
            new Identifier('ChildClass'),
            ['extends' => new Name('ParentClass')]
        );
        $result = $this->extractor->extractClass($class, '');

        $meta = json_decode($result['meta'], true);
        $this->assertEquals('ParentClass', $meta['extends']);
    }

    public function testExtractClassWithImplements(): void
    {
        $class = new Class_(
            new Identifier('ImplClass'),
            ['implements' => [new Name('InterfaceA'), new Name('InterfaceB')]]
        );
        $result = $this->extractor->extractClass($class, '');

        $meta = json_decode($result['meta'], true);
        $this->assertContains('InterfaceA', $meta['implements']);
        $this->assertContains('InterfaceB', $meta['implements']);
    }

    public function testExtractInterface(): void
    {
        $interface = new \PhpParser\Node\Stmt\Interface_(new Identifier('TestInterface'));
        $result = $this->extractor->extractInterface($interface, 'App\\Contracts');

        $this->assertEquals('interface', $result['chunk_type']);
        $this->assertEquals('TestInterface', $result['name']);
        $this->assertEquals('App\\Contracts', $result['namespace']);
        $this->assertTrue($result['is_abstract']);
    }

    public function testExtractTrait(): void
    {
        $trait = new \PhpParser\Node\Stmt\Trait_(new Identifier('TestTrait'));
        $result = $this->extractor->extractTrait($trait, 'App\\Traits');

        $this->assertEquals('trait', $result['chunk_type']);
        $this->assertEquals('TestTrait', $result['name']);
        $this->assertEquals('App\\Traits', $result['namespace']);
    }

    public function testExtractFunction(): void
    {
        $function = new \PhpParser\Node\Stmt\Function_(new Identifier('testFunction'));
        $result = $this->extractor->extractFunction($function, 'App\\Helpers');

        $this->assertEquals('function', $result['chunk_type']);
        $this->assertEquals('testFunction', $result['name']);
        $this->assertEquals('global', $result['visibility']);
    }

    public function testExtractMethod(): void
    {
        $method = new \PhpParser\Node\Stmt\ClassMethod(new Identifier('testMethod'));
        $result = $this->extractor->extractMethod($method, 'TestClass', 'App\\Models');

        $this->assertEquals('method', $result['chunk_type']);
        $this->assertEquals('testMethod', $result['name']);
        $this->assertEquals('TestClass', $result['parent_name']);
    }

    public function testExtractProperty(): void
    {
        $prop = new \PhpParser\Node\Stmt\Property(
            \PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC,
            [new \PhpParser\Node\PropertyItem('testProp')]
        );
        $result = $this->extractor->extractProperty($prop, 'TestClass', 'App\\Models');

        $this->assertEquals('property', $result['chunk_type']);
        $this->assertEquals('testProp', $result['name']);
        $this->assertEquals('public', $result['visibility']);
    }

    public function testExtractClassConstant(): void
    {
        $const = new \PhpParser\Node\Stmt\ClassConst(
            [new \PhpParser\Node\Const_('TEST_CONST', new \PhpParser\Node\Scalar\String_('value'))]
        );
        $result = $this->extractor->extractClassConstant($const, 'TestClass', 'App\\Models');

        $this->assertEquals('constant', $result['chunk_type']);
        $this->assertEquals('TEST_CONST', $result['name']);
        $this->assertTrue($result['is_static']);
    }

    public function testExtractImport(): void
    {
        $use = new \PhpParser\Node\Stmt\Use_([
            new \PhpParser\Node\Stmt\UseUse(new Name('App\\Models\\User'))
        ]);
        $result = $this->extractor->extractImport($use, 'App\\Controllers');

        $this->assertEquals('import', $result['chunk_type']);
        $meta = json_decode($result['meta'], true);
        $this->assertCount(1, $meta['uses']);
        $this->assertEquals('App\\Models\\User', $meta['uses'][0]['name']);
    }

    public function testGetDocblock(): void
    {
        $docComment = new \PhpParser\Comment\Doc("/**\n * Test docblock\n * @return void\n */");
        $class = new Class_(new Identifier('TestClass'));
        $class->setDocComment($docComment);
        $result = $this->extractor->extractClass($class, '');

        $this->assertNotNull($result['docblock']);
        $this->assertStringContainsString('Test docblock', $result['docblock']);
    }
}
