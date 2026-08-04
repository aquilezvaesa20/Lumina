<?php

declare(strict_types=1);

namespace Lumina\Tests\Unit\Ai;

use Lumina\Ai\PromptBuilder;
use PHPUnit\Framework\TestCase;

class PromptBuilderTest extends TestCase
{
    private PromptBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PromptBuilder();
    }

    public function testBuildEnrichmentPromptContainsAllSections(): void
    {
        $source = [
            'filename' => 'AuthService.php',
            's3_key' => 'src/Auth/AuthService.php',
            'size_bytes' => 2048,
        ];
        $chunks = [
            [
                'chunk_type' => 'class',
                'name' => 'AuthService',
                'namespace' => 'App\\Services',
                'content' => 'class AuthService { public function login() {} }',
            ],
        ];
        $relations = [];
        $staticDossier = [];

        $prompt = $this->builder->buildEnrichmentPrompt(
            $source,
            $chunks,
            $relations,
            $staticDossier
        );

        $this->assertStringContainsString('AuthService.php', $prompt);
        $this->assertStringContainsString('App\Services', $prompt);
        $this->assertStringContainsString('¿Dónde está?', $prompt);
        $this->assertStringContainsString('¿Qué hace?', $prompt);
        $this->assertStringContainsString('JSON', $prompt);
    }

    public function testBuildCodePreviewTruncatesLongCode(): void
    {
        $longChunk = [
            'chunk_type' => 'class',
            'name' => 'LongClass',
            'content' => str_repeat('// line', 2000),
        ];

        $method = new \ReflectionMethod(PromptBuilder::class, 'buildCodePreview');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->builder, [$longChunk]);
        
        $this->assertLessThan(9000, strlen($result));
        $this->assertStringContainsString('truncado', $result);
    }
}
