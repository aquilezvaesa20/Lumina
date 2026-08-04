<?php

declare(strict_types=1);

namespace Lumina\Parser;

use Lumina\Core\Config;
use Lumina\Core\Database;
use PhpParser\NodeTraverser;
use Symfony\Component\Finder\Finder;

/**
 * Orquestador principal que coordina todo el proceso de análisis de código PHP.
 * 
 * El Chunker se encarga de:
 * 1. Recorrer un directorio buscando archivos .php
 * 2. Parsear cada archivo con nikic/php-parser
 * 3. Extraer todos los chunks de código (clases, métodos, funciones, etc.)
 * 4. Almacenar los chunks en la tabla SourceChunks
 * 5. Actualizar ProjectSources con el estado de indexación
 */
class Chunker
{
    private PhpParser $parser;
    private AstVisitor $visitor;
    private Config $config;
    private Database $db;

    public function __construct(Config $config, Database $db)
    {
        $this->config = $config;
        $this->db = $db;
        $this->parser = new PhpParser();
        $this->visitor = new AstVisitor();
    }

    /**
     * Analiza un directorio completo y extrae todos los chunks
     *
     * @param string $directory Directorio a analizar
     * @param int $projectId ID del proyecto en la BD
     * @return array<string, mixed> Resumen del análisis
     * @throws \InvalidArgumentException Si el directorio no existe
     */
    public function analyzeDirectory(string $directory, int $projectId): array
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Directory not found: {$directory}");
        }

        $excludeDirs = $this->config->get('parser.exclude_dirs', []);
        $extensions = $this->config->get('parser.include_extensions', ['php']);

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->exclude($excludeDirs)
            ->name('*.' . implode(', *.', $extensions));

        $stats = [
            'files_found' => 0,
            'files_parsed' => 0,
            'files_failed' => 0,
            'chunks_extracted' => 0,
            'errors' => [],
        ];

        foreach ($finder as $file) {
            $stats['files_found']++;
            
            try {
                $result = $this->analyzeFile($file->getRealPath(), $projectId);
                
                if ($result['success']) {
                    $stats['files_parsed']++;
                    $stats['chunks_extracted'] += $result['chunks_count'];
                } else {
                    $stats['files_failed']++;
                    $stats['errors'][] = [
                        'file' => $file->getRealPath(),
                        'errors' => $result['errors'],
                    ];
                }
            } catch (\Throwable $e) {
                $stats['files_failed']++;
                $stats['errors'][] = [
                    'file' => $file->getRealPath(),
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        return $stats;
    }

    /**
     * Analiza un archivo PHP individual
     *
     * @param string $filePath Ruta absoluta al archivo
     * @param int $projectId ID del proyecto
     * @return array{success: bool, chunks_count: int, errors: array}
     */
    public function analyzeFile(string $filePath, int $projectId): array
    {
        // 1. Parsear el archivo
        $parseResult = $this->parser->parseFile($filePath);
        
        if ($parseResult['ast'] === null) {
            return [
                'success' => false,
                'chunks_count' => 0,
                'errors' => $parseResult['errors'],
            ];
        }

        // 2. Obtener o crear el registro en ProjectSources
        $sourceId = $this->getOrCreateSource($filePath, $projectId);
        
        if ($sourceId === null) {
            return [
                'success' => false,
                'chunks_count' => 0,
                'errors' => ['Failed to create ProjectSource record'],
            ];
        }

        // 3. Extraer chunks del AST
        $this->visitor->reset();
        
        $traverser = new NodeTraverser();
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($parseResult['ast']);

        $chunks = $this->visitor->getChunks();

        // 4. Almacenar chunks en la BD
        $chunksInserted = 0;
        foreach ($chunks as $chunk) {
            try {
                $this->insertChunk($chunk, $sourceId, $projectId);
                $chunksInserted++;
            } catch (\Throwable $e) {
                // Continuar con otros chunks aunque uno falle
                error_log("Failed to insert chunk: {$e->getMessage()}");
            }
        }

        // 5. Actualizar estado de ProjectSources
        $this->db->query(
            "UPDATE ProjectSources SET status = 'indexed', indexed_at = NOW() WHERE id_ = ?",
            [$sourceId]
        );

        return [
            'success' => true,
            'chunks_count' => $chunksInserted,
            'errors' => $parseResult['errors'],
        ];
    }

    /**
     * Obtiene o crea un registro en ProjectSources
     *
     * @param string $filePath Ruta al archivo
     * @param int $projectId ID del proyecto
     * @return int|null ID del registro o null si falla
     */
    private function getOrCreateSource(string $filePath, int $projectId): ?int
    {
        $s3Key = $filePath; // En producción, esto sería la clave S3 real
        $filename = basename($filePath);
        $sizeBytes = filesize($filePath);
        $sha256 = hash_file('sha256', $filePath);

        // Verificar si ya existe
        $existing = $this->db->fetchOne(
            "SELECT id_ FROM ProjectSources WHERE project_id_ = ? AND s3_key = ?",
            [$projectId, $s3Key]
        );

        if ($existing) {
            return (int) $existing['id_'];
        }

        // Crear nuevo registro
        try {
            $sourceId = $this->db->insert('ProjectSources', [
                'project_id_' => $projectId,
                's3_key' => $s3Key,
                'filename' => $filename,
                'mime_type' => 'text/x-php',
                'size_bytes' => $sizeBytes,
                'language' => 'php',
                'sha256' => $sha256,
                'status' => 'pending',
            ]);

            return $sourceId;
        } catch (\Throwable $e) {
            error_log("Failed to create ProjectSource: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Inserta un chunk en la tabla SourceChunks
     *
     * @param array<string, mixed> $chunk Datos del chunk
     * @param int $sourceId ID del source
     * @param int $projectId ID del proyecto
     * @return int ID del chunk insertado
     */
    private function insertChunk(array $chunk, int $sourceId, int $projectId): int
    {
        $content = $chunk['content'];
        $checksum = hash('sha256', $content);
        
        // Estimar token count (aproximación: 4 chars por token)
        $tokenCount = (int) ceil(strlen($content) / 4);

        $data = [
            'source_id_' => $sourceId,
            'project_id_' => $projectId,
            'chunk_type' => $chunk['chunk_type'],
            'name' => $chunk['name'],
            'parent_name' => $chunk['parent_name'],
            'signature' => $chunk['signature'],
            'content' => $content,
            'start_line' => $chunk['start_line'],
            'end_line' => $chunk['end_line'],
            'token_count' => $tokenCount,
            'checksum' => $checksum,
            'meta' => $chunk['meta'],
            'visibility' => $chunk['visibility'],
            'is_static' => $chunk['is_static'] ? 1 : 0,
            'is_abstract' => $chunk['is_abstract'] ? 1 : 0,
            'is_final' => $chunk['is_final'] ? 1 : 0,
            'namespace' => $chunk['namespace'],
            'docblock' => $chunk['docblock'],
            'return_type' => $chunk['return_type'],
            'parameters_json' => $chunk['parameters_json'],
        ];

        return $this->db->insert('SourceChunks', $data);
    }
}
