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
        'use_ai' => false,
        'ai_provider' => 'anthropic',
        'ai_model' => 'claude-3-5-sonnet-20241022',
    ],

    'ai' => [
        'provider' => $_ENV['AI_PROVIDER'] ?? 'anthropic',
        'anthropic' => [
            'api_key' => $_ENV['ANTHROPIC_API_KEY'] ?? '',
            'model' => $_ENV['ANTHROPIC_MODEL'] ?? 'claude-3-5-sonnet-20241022',
            'api_version' => '2023-06-01',
            'max_tokens' => 4096,
        ],
        'openai' => [
            'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
            'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-4o',
        ],
        'rate_limit' => [
            'requests_per_minute' => 20,
            'delay_between_requests' => 1, // segundos
        ],
    ],
];
