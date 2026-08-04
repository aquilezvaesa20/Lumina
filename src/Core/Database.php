<?php

declare(strict_types=1);

namespace Lumina\Core;

use PDO;
use PDOException;

/**
 * Wrapper PDO para la base de datos de Lumina.
 * 
 * Proporciona una interfaz simplificada para operaciones comunes de base de datos
 * con conexión lazy (solo se conecta cuando es necesario).
 */
class Database
{
    private ?PDO $pdo = null;

    /**
     * @param array<string, mixed> $config Configuración de la base de datos
     */
    public function __construct(private array $config)
    {
    }

    /**
     * Obtiene la conexión PDO (lazy connection).
     * 
     * @throws \RuntimeException Si falla la conexión
     */
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

    /**
     * Ejecuta una consulta SQL con parámetros opcionales.
     * 
     * @param string $sql La consulta SQL
     * @param array<int|string, mixed> $params Parámetros para la consulta
     * @return \PDOStatement El statement ejecutado
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtiene un único registro de la base de datos.
     * 
     * @param string $sql La consulta SQL
     * @param array<int|string, mixed> $params Parámetros para la consulta
     * @return array<string, mixed>|null El registro o null si no existe
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    /**
     * Obtiene todos los registros de la base de datos.
     * 
     * @param string $sql La consulta SQL
     * @param array<int|string, mixed> $params Parámetros para la consulta
     * @return array<int, array<string, mixed>> Los registros
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Inserta un registro en la tabla especificada.
     * 
     * @param string $table Nombre de la tabla
     * @param array<string, mixed> $params Datos a insertar
     * @return int El ID del registro insertado
     */
    public function insert(string $table, array $params): int
    {
        $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($params)));
        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($params));

        return (int) $this->getConnection()->lastInsertId();
    }
}
