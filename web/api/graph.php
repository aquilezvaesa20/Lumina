<?php

declare(strict_types=1);

/**
 * GET /api/graph?project_id=1&filter=class
 * 
 * Retorna el grafo completo en formato Cytoscape.js
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use Lumina\Core\Config;
use Lumina\Core\Database;

try {
    $config = Config::load();
    $db = new Database($config->getDatabaseConfig());

    $projectId = (int)($_GET['project_id'] ?? 1);
    $filterType = $_GET['filter'] ?? null; // class, function, interface, etc.
    $maxNodes = (int)($_GET['max_nodes'] ?? 500);

    // 1. Obtener chunks (nodos)
    $chunkQuery = "SELECT 
        sc.id_ AS chunk_id,
        sc.chunk_type,
        sc.name,
        sc.parent_name,
        sc.namespace,
        sc.visibility,
        sc.is_static,
        sc.is_abstract,
        sc.is_final,
        sc.signature,
        ps.filename,
        ps.id_ AS source_id
    FROM SourceChunks sc
    JOIN ProjectSources ps ON sc.source_id_ = ps.id_
    WHERE sc.project_id_ = ?
    AND sc.chunk_type IN ('class', 'interface', 'trait', 'function', 'method')
    AND sc.name IS NOT NULL";

    $params = [$projectId];

    if ($filterType) {
        $chunkQuery .= " AND sc.chunk_type = ?";
        $params[] = $filterType;
    }

    $chunkQuery .= " ORDER BY sc.chunk_type, sc.name LIMIT ?";
    $params[] = $maxNodes;

    $chunks = $db->fetchAll($chunkQuery, $params);

    if (empty($chunks)) {
        echo json_encode([
            'nodes' => [],
            'edges' => [],
            'stats' => ['nodes' => 0, 'edges' => 0],
            'message' => 'No chunks found. Run: ./bin/lumina analyze && ./bin/lumina populate'
        ]);
        exit;
    }

    $chunkIds = array_column($chunks, 'chunk_id');

    // 2. Obtener relaciones (aristas) solo entre los chunks seleccionados
    $placeholders = implode(',', array_fill(0, count($chunkIds), '?'));
    $relations = $db->fetchAll(
        "SELECT 
            cr.source_chunk_id_,
            cr.target_chunk_id_,
            cr.relation_type,
            cr.is_confirmed,
            cr.context
        FROM ChunkRelations cr
        WHERE cr.source_chunk_id_ IN ({$placeholders})
        AND cr.target_chunk_id_ IN ({$placeholders})
        AND cr.project_id_ = ?",
        array_merge($chunkIds, $chunkIds, [$projectId])
    );

    // 3. Construir nodos en formato Cytoscape
    $nodes = [];
    foreach ($chunks as $chunk) {
        $nodes[] = [
            'data' => [
                'id' => (string)$chunk['chunk_id'],
                'label' => $chunk['name'],
                'type' => $chunk['chunk_type'],
                'namespace' => $chunk['namespace'] ?? '',
                'filename' => $chunk['filename'],
                'visibility' => $chunk['visibility'],
                'is_static' => (bool)$chunk['is_static'],
                'is_abstract' => (bool)$chunk['is_abstract'],
                'is_final' => (bool)$chunk['is_final'],
                'parent' => $chunk['parent_name'],
                'signature' => $chunk['signature'],
                'source_id' => (string)$chunk['source_id'],
            ]
        ];
    }

    // 4. Construir aristas en formato Cytoscape
    $edges = [];
    foreach ($relations as $rel) {
        $edges[] = [
            'data' => [
                'id' => $rel['source_chunk_id_'] . '-' . $rel['target_chunk_id_'] . '-' . $rel['relation_type'],
                'source' => (string)$rel['source_chunk_id_'],
                'target' => (string)$rel['target_chunk_id_'],
                'relation' => $rel['relation_type'],
                'confirmed' => (bool)$rel['is_confirmed'],
                'context' => $rel['context'],
            ]
        ];
    }

    // 5. Stats
    $typeCounts = [];
    foreach ($chunks as $chunk) {
        $type = $chunk['chunk_type'];
        $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
    }

    echo json_encode([
        'nodes' => $nodes,
        'edges' => $edges,
        'stats' => [
            'nodes' => count($nodes),
            'edges' => count($edges),
            'types' => $typeCounts,
            'project_id' => $projectId,
        ],
        'generated_at' => date('c'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
