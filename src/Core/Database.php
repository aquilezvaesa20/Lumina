<?php

declare(strict_types=1);

namespace Lumina\Core;

use PDO;
use PDOException;

/**
 * Wrapper PDO para la base de datos de Lumina
 */
class Database
{
    private ?PDO $pdo = null;

    public function __construct(private array $config)
    {
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $this->config['host'] ?? 'localhost',
                $this->config['port'] ?? 3306,
                $this->config['database'] ?? 'adbbmis1_Cloud'
            );

            try {
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'] ?? 'root',
                    $this->config['password'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException('Error de conexión a BD: ' . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int) $this->getConnection()->lastInsertId();
    }
}
