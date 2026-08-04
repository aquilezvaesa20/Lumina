<?php

declare(strict_types=1);

namespace Lumina\Tests\Unit\Dossier;

use Lumina\Dossier\QuestionAnswerer;
use Lumina\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para QuestionAnswerer
 */
class QuestionAnswererTest extends TestCase
{
    private QuestionAnswerer $answerer;
    private Database $db;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Database::class);
        $this->answerer = new QuestionAnswerer($this->db);
    }

    /**
     * Test para answerWhereIs con datos básicos
     */
    public function testAnswerWhereIsBasic(): void
    {
        $source = [
            'filename' => 'AuthService.php',
            's3_key' => 'src/Auth/AuthService.php',
            'language' => 'php',
            'size_bytes' => 1024,
        ];
        $chunks = [
            ['namespace' => 'App\\Services', 'chunk_type' => 'class', 'name' => 'AuthService'],
        ];

        $result = $this->answerer->answerWhereIs($source, $chunks);

        $this->assertStringContainsString('AuthService.php', $result);
        $this->assertStringContainsString('App\\Services', $result);
        $this->assertStringContainsString('php', $result);
        $this->assertStringContainsString('1024 bytes', $result);
    }

    /**
     * Test para answerWhatDoes con una clase
     */
    public function testAnswerWhatDoesWithClass(): void
    {
        $source = ['filename' => 'AuthService.php'];
        $chunks = [
            [
                'chunk_type' => 'class',
                'name' => 'AuthService',
                'namespace' => 'App\\Services',
                'docblock' => "/**\n * Gestiona la autenticación de usuarios\n */",
            ],
        ];

        $result = $this->answerer->answerWhatDoes($source, $chunks);

        $this->assertStringContainsString('AuthService', $result);
        $this->assertStringContainsString('clase', $result);
    }

    /**
     * Test para inferPurposeFromName usando reflexión
     */
    public function testInferPurposeFromName(): void
    {
        // Usar reflexión para probar el método privado
        $method = new \ReflectionMethod(QuestionAnswerer::class, 'inferPurposeFromName');
        $method->setAccessible(true);

        $this->assertNotNull($method->invoke($this->answerer, 'AuthService'));
        $this->assertNotNull($method->invoke($this->answerer, 'UserController'));
        $this->assertNotNull($method->invoke($this->answerer, 'PaymentService'));
        $this->assertNull($method->invoke($this->answerer, 'Xyz123'));
    }

    /**
     * Test para answerWhyExists con propósito inferido
     */
    public function testAnswerWhyExistsWithInferredPurpose(): void
    {
        $source = ['filename' => 'UserService.php'];
        $chunks = [
            [
                'chunk_type' => 'class',
                'name' => 'UserService',
            ],
        ];

        $result = $this->answerer->answerWhyExists($source, $chunks);

        $this->assertNotNull($result);
        $this->assertStringContainsString('gestionar entidades y operaciones de usuarios', $result ?? '');
    }

    /**
     * Test para answerHowDoes con clase que tiene métodos
     */
    public function testAnswerHowDoesWithMethods(): void
    {
        $source = ['filename' => 'UserRepository.php'];
        $chunks = [
            [
                'chunk_type' => 'class',
                'name' => 'UserRepository',
                'namespace' => 'App\\Repositories',
            ],
            [
                'chunk_type' => 'method',
                'name' => 'find',
                'parent_name' => 'UserRepository',
                'visibility' => 'public',
                'return_type' => '?User',
                'is_static' => false,
            ],
            [
                'chunk_type' => 'method',
                'name' => 'save',
                'parent_name' => 'UserRepository',
                'visibility' => 'public',
                'return_type' => 'bool',
                'is_static' => false,
            ],
        ];

        $result = $this->answerer->answerHowDoes($source, $chunks);

        $this->assertStringContainsString('UserRepository', $result);
        $this->assertStringContainsString('Métodos', $result);
        $this->assertStringContainsString('find', $result);
        $this->assertStringContainsString('save', $result);
    }

    /**
     * Test para detectMainType con múltiples tipos
     */
    public function testDetectMainTypePriority(): void
    {
        $method = new \ReflectionMethod(QuestionAnswerer::class, 'detectMainType');
        $method->setAccessible(true);

        // Clase e interface → debe priorizar class
        $chunks = [
            ['chunk_type' => 'interface', 'name' => 'UserInterface'],
            ['chunk_type' => 'class', 'name' => 'User'],
        ];
        $this->assertEquals('class', $method->invoke($this->answerer, $chunks));

        // Solo funciones
        $chunks = [
            ['chunk_type' => 'function', 'name' => 'helper'],
        ];
        $this->assertEquals('function', $method->invoke($this->answerer, $chunks));

        // Desconocido
        $chunks = [
            ['chunk_type' => 'constant', 'name' => 'CONST'],
        ];
        $this->assertEquals('desconocido', $method->invoke($this->answerer, $chunks));
    }

    /**
     * Test para extractDocblockSummary
     */
    public function testExtractDocblockSummary(): void
    {
        $method = new \ReflectionMethod(QuestionAnswerer::class, 'extractDocblockSummary');
        $method->setAccessible(true);

        $docblock = "/**\n * Esta es la descripción principal\n * @param string \$name\n * @return void\n */";
        $this->assertEquals('Esta es la descripción principal', $method->invoke($this->answerer, $docblock));

        $docblock = "/** Simple comment */";
        $this->assertEquals('Simple comment', $method->invoke($this->answerer, $docblock));
    }

    /**
     * Test para answerInteractsWith sin dependencias
     */
    public function testAnswerInteractsWithEmpty(): void
    {
        $sourceId = 1;
        $projectId = 1;

        $this->db->expects($this->once())
            ->method('fetchAll')
            ->with(
                "SELECT id_, name, chunk_type FROM SourceChunks WHERE source_id_ = ?",
                [$sourceId]
            )
            ->willReturn([]);

        $result = $this->answerer->answerInteractsWith($sourceId, $projectId);

        $this->assertEquals([], $result['outgoing']);
        $this->assertEquals([], $result['incoming']);
        $this->assertEquals('Sin dependencias detectadas', $result['summary']);
    }
}
