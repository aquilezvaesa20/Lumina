<?php

declare(strict_types=1);

namespace Lumina\Populator;

use Lumina\Core\Database;

/**
 * Construye el grafo de dependencias y lo almacena en la BD.
 * 
 * Coordina el análisis de todos los chunks de un proyecto, detecta las relaciones
 * entre ellos y las almacena en la tabla ChunkRelations.
 */
class DependencyGraph
{
    private Database $db;
    private RelationDetector $detector;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->detector = new RelationDetector();
    }

    /**
     * Construye el grafo de dependencias para un proyecto
     *
     * @param int $projectId ID del proyecto
     * @return array Estadísticas del proceso
     */
    public function buildForProject(int $projectId): array
    {
        $stats = [
            'chunks_analyzed' => 0,
            'relations_found' => 0,
            'relations_inserted' => 0,
            'relations_skipped' => 0,
            'unresolved_targets' => [],
        ];

        // 1. Obtener todos los archivos del proyecto
        $sources = $this->db->fetchAll(
            "SELECT id_, filename FROM ProjectSources WHERE project_id_ = ? AND status = 'indexed'",
            [$projectId]
        );

        foreach ($sources as $source) {
            $result = $this->buildForSource((int)$source['id_'], $projectId);
            
            $stats['chunks_analyzed'] += $result['chunks_analyzed'];
            $stats['relations_found'] += $result['relations_found'];
            $stats['relations_inserted'] += $result['relations_inserted'];
            $stats['relations_skipped'] += $result['relations_skipped'];
            $stats['unresolved_targets'] = array_merge(
                $stats['unresolved_targets'],
                $result['unresolved_targets']
            );
        }

        return $stats;
    }

    /**
     * Construye relaciones para un archivo fuente específico
     *
     * @param int $sourceId ID del archivo fuente
     * @param int $projectId ID del proyecto
     * @return array Estadísticas del proceso
     */
    public function buildForSource(int $sourceId, int $projectId): array
    {
        $stats = [
            'chunks_analyzed' => 0,
            'relations_found' => 0,
            'relations_inserted' => 0,
            'relations_skipped' => 0,
            'unresolved_targets' => [],
        ];

        // 1. Obtener todos los chunks del archivo
        $chunks = $this->db->fetchAll(
            "SELECT * FROM SourceChunks WHERE source_id_ = ? AND project_id_ = ?",
            [$sourceId, $projectId]
        );

        if (empty($chunks)) {
            return $stats;
        }

        // 2. Obtener imports y namespace del archivo
        $fileImports = array_filter($chunks, fn($c) => $c['chunk_type'] === 'import');
        $fileNamespace = '';
        
        foreach ($chunks as $chunk) {
            if (!empty($chunk['namespace'])) {
                $fileNamespace = $chunk['namespace'];
                break;
            }
        }

        // 3. Analizar cada chunk (excepto imports, ya los usamos para resolver)
        foreach ($chunks as $chunk) {
            if ($chunk['chunk_type'] === 'import') {
                continue;
            }

            $stats['chunks_analyzed']++;

            // Detectar relaciones
            $detected = $this->detector->detectRelations(
                $chunk,
                $fileImports,
                $fileNamespace
            );

            $stats['relations_found'] += count($detected);

            // 4. Resolver targets y crear relaciones
            foreach ($detected as $relation) {
                $targetChunkId = $this->resolveTargetChunk(
                    $relation['target_fqn'],
                    $relation['target_short_name'],
                    $projectId
                );

                if ($targetChunkId === null) {
                    $stats['relations_skipped']++;
                    $stats['unresolved_targets'][] = $relation['target_fqn'];
                    continue;
                }

                // No crear relación a sí mismo
                if ($targetChunkId === (int)$chunk['id_']) {
                    $stats['relations_skipped']++;
                    continue;
                }

                // 5. Insertar relación (ignorar duplicados)
                try {
                    $this->db->query(
                        "INSERT IGNORE INTO ChunkRelations 
                         (source_chunk_id_, target_chunk_id_, project_id_, relation_type, 
                          context, context_line, is_confirmed, meta)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            (int)$chunk['id_'],
                            $targetChunkId,
                            $projectId,
                            $relation['relation_type']->value,
                            $relation['context'],
                            $relation['context_line'],
                            $relation['is_confirmed'] ? 1 : 0,
                            json_encode([
                                'target_fqn' => $relation['target_fqn'],
                            ]),
                        ]
                    );
                    $stats['relations_inserted']++;
                } catch (\Throwable $e) {
                    $stats['relations_skipped']++;
                }
            }

            // 6. Crear relación CONTAINS para métodos dentro de clases
            if ($chunk['chunk_type'] === 'method' && !empty($chunk['parent_name'])) {
                $parentChunkId = $this->resolveTargetChunk(
                    $fileNamespace . '\\' . $chunk['parent_name'],
                    $chunk['parent_name'],
                    $projectId
                );

                if ($parentChunkId !== null && $parentChunkId !== (int)$chunk['id_']) {
                    try {
                        $this->db->query(
                            "INSERT IGNORE INTO ChunkRelations 
                             (source_chunk_id_, target_chunk_id_, project_id_, relation_type, context)
                             VALUES (?, ?, ?, 'contains', ?)",
                            [
                                $parentChunkId,
                                (int)$chunk['id_'],
                                $projectId,
                                $chunk['parent_name'] . ' contiene ' . $chunk['name'],
                            ]
                        );
                        $stats['relations_inserted']++;
                    } catch (\Throwable $e) {
                        // Ignorar duplicados
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Resuelve un FQN a un chunk_id en la BD
     *
     * @param string $fqn FQN completo
     * @param string $shortName Nombre corto
     * @param int $projectId ID del proyecto
     * @return int|null ID del chunk o null si no se encuentra
     */
    private function resolveTargetChunk(
        string $fqn,
        string $shortName,
        int $projectId
    ): ?int {
        // Intentar por FQN completo (namespace + nombre)
        $parts = explode('\\', $fqn);
        $name = array_pop($parts);
        $namespace = implode('\\', $parts);

        // Buscar por nombre y namespace
        $result = $this->db->fetchOne(
            "SELECT id_ FROM SourceChunks 
             WHERE project_id_ = ? AND name = ? AND namespace = ?
             AND chunk_type IN ('class', 'interface', 'trait', 'function')
             LIMIT 1",
            [$projectId, $name, $namespace]
        );

        if ($result) {
            return (int)$result['id_'];
        }

        // Si no se encuentra por namespace exacto, buscar solo por nombre
        // (puede ser una clase global o un alias no resuelto)
        $result = $this->db->fetchOne(
            "SELECT id_ FROM SourceChunks 
             WHERE project_id_ = ? AND name = ?
             AND chunk_type IN ('class', 'interface', 'trait', 'function')
             LIMIT 1",
            [$projectId, $shortName]
        );

        return $result ? (int)$result['id_'] : null;
    }
}
