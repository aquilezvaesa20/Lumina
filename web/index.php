<?php

declare(strict_types=1);

/**
 * Lumina Web - Router para API y frontend
 * 
 * Uso: php -S localhost:8080 web/index.php
 * O desde CLI: ./bin/lumina visualize 1
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Parsear URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// CORS headers (para desarrollo)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Routing simple
$routes = [
    // API endpoints
    '/api/graph' => __DIR__ . '/api/graph.php',
    '/api/node' => __DIR__ . '/api/node.php',
    '/api/stats' => __DIR__ . '/api/stats.php',
    
    // Frontend
    '/' => __DIR__ . '/public/index.html',
    '/index.html' => __DIR__ . '/public/index.html',
];

// Buscar ruta que coincida
$matched = false;
foreach ($routes as $route => $file) {
    if ($uri === $route || str_starts_with($uri, $route . '?')) {
        if (file_exists($file)) {
            if (str_ends_with($file, '.php')) {
                require $file;
            } else {
                // Servir archivos estáticos
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mimeTypes = [
                    'html' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'json' => 'application/json',
                    'png' => 'image/png',
                    'svg' => 'image/svg+xml',
                ];
                header('Content-Type: ' . ($mimeTypes[$ext] ?? 'text/plain'));
                readfile($file);
            }
            $matched = true;
            break;
        }
    }
}

// Servir archivos estáticos de /public/ si no matcheó ninguna ruta
if (!$matched) {
    $publicFile = __DIR__ . '/public' . $uri;
    if (file_exists($publicFile) && is_file($publicFile)) {
        $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
        $mimeTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
        ];
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($publicFile);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found', 'uri' => $uri]);
    }
}
