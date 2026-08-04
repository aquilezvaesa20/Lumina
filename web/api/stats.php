<?php

declare(strict_types=1);

/**
 * GET /api/stats?project_id=1
 * 
 * Retorna estadísticas agregadas del proyecto
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use Lumina\Core\Config;
use Lumina\Core\Database;

try {
    $config = Config::load();
    $db = new Database($config->getDatabaseConfig());

    $projectId = (int)($_GET['project_id'] ?? 1);

    $stats = [
        'project_id' => $projectId,
        'files' => (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM ProjectSources WHERE project_id_ = ?",
            [$projectId]
        )['c'],
        'chunks' => (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM SourceChunks WHERE project_id_ = ?",
            [$projectId]
        )['c'],
        'relations' => (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM ChunkRelations WHERE project_id_ = ?",
            [$projectId]
        )['c'],
        'dossiers' => (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM FileDossiers WHERE project_id_ = ?",
            [$projectId]
        )['c'],
        'ai_enriched' => (int)$db->fetchOne(
            "SELECT COUNT(*) as c FROM FileDossiers WHERE project_id_ = ? AND ai_generated = 1",
            [$projectId]
        )['c'],
        'chunks_by_type' => $db->fetchAll(
            "SELECT chunk_type, COUNT(*) as count 
             FROM SourceChunks 
             WHERE project_id_ = ? 
             GROUP BY chunk_type 
             ORDER BY count DESC",
            [$projectId]
        ),
        'relations_by_type' => $db->fetchAll(
            "SELECT relation_type, COUNT(*) as count 
             FROM ChunkRelations 
             WHERE project_id_ = ? 
             GROUP BY relation_type 
             ORDER BY count DESC",
            [$projectId]
        ),
    ];

    echo json_encode($stats, JSON_PRETTY_PRINT);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
