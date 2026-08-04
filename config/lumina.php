<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Lumina',
        'version' => '1.0.0',
        'debug' => (bool) ($_ENV['LUMINA_DEBUG'] ?? false),
    ],

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_NAME'] ?? 'adbbmis1_Cloud',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
    ],

    'parser' => [
        'php_version' => '8.2',
        'prefer_php7_parser' => false,
        'throw_on_error' => true,
        'exclude_dirs' => [
            'vendor',
            'node_modules',
            '.git',
            'tests',
            'cache',
            'storage',
        ],
        'include_extensions' => ['php'],
        'max_file_size_kb' => 1024, // 1MB
    ],

    'analysis' => [
        'batch_size' => 50,
        'max_depth' => 10,
        'include_tests' => false,
        'follow_symlinks' => false,
    ],

    'dossier' => [
        'auto_generate' => true,
        'use_ai' => false, // Se activará en Fase 6
        'ai_provider' => 'anthropic',
        'ai_model' => 'claude-3-5-sonnet-20241022',
    ],
];
