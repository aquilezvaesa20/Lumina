<?php

declare(strict_types=1);

/**
 * GET /api/node?chunk_id=123
 * 
 * Retorna detalles completos de un chunk incluyendo su dossier
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use Lumina\Core\Config;
use Lumina\Core\Database;

try {
    $config = Config::load();
    $db = new Database($config->getDatabaseConfig());

    $chunkId = (int)($_GET['chunk_id'] ?? 0);
    
    if ($chunkId <= 0) {
        throw new \InvalidArgumentException('chunk_id is required');
    }

    // 1. Obtener información del chunk
    $chunk = $db->fetchOne(
        "SELECT 
            sc.*,
            ps.filename,
            ps.s3_key,
            ps.size_bytes
        FROM SourceChunks sc
        JOIN ProjectSources ps ON sc.source_id_ = ps.id_
        WHERE sc.id_ = ?",
        [$chunkId]
    );

    if (!$chunk) {
        http_response_code(404);
        echo json_encode(['error' => 'Chunk not found']);
        exit;
    }

    // 2. Obtener dossier del archivo (si existe)
    $dossier = $db->fetchOne(
        "SELECT * FROM FileDossiers WHERE source_id_ = ?",
        [$chunk['source_id_']]
    );

    // 3. Obtener relaciones (entrantes y salientes)
    $outgoing = $db->fetchAll(
        "SELECT 
            cr.relation_type,
            cr.context,
            cr.is_confirmed,
            tc.id_ AS target_id,
            tc.name AS target_name,
            tc.chunk_type AS target_type,
            tc.namespace AS target_namespace
        FROM ChunkRelations cr
        JOIN SourceChunks tc ON cr.target_chunk_id_ = tc.id_
        WHERE cr.source_chunk_id_ = ?",
        [$chunkId]
    );

    $incoming = $db->fetchAll(
        "SELECT 
            cr.relation_type,
            cr.context,
            cr.is_confirmed,
            sc.id_ AS source_id,
            sc.name AS source_name,
            sc.chunk_type AS source_type,
            sc.namespace AS source_namespace
        FROM ChunkRelations cr
        JOIN SourceChunks sc ON cr.source_chunk_id_ = sc.id_
        WHERE cr.target_chunk_id_ = ?",
        [$chunkId]
    );

    echo json_encode([
        'chunk' => $chunk,
        'dossier' => $dossier,
        'relations' => [
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'outgoing_count' => count($outgoing),
            'incoming_count' => count($incoming),
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
