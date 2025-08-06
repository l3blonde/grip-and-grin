<?php

declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private PDO $connection;

    public function __construct(string $dsn, string $username, string $password)
    {
        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function fromUrl(string $databaseUrl): self
    {
        $url = parse_url($databaseUrl);

        if (!$url) {
            throw new \InvalidArgumentException('Invalid database URL format');
        }

        $host = $url['host'] ?? 'localhost';
        $port = $url['port'] ?? 3306;
        $database = ltrim($url['path'] ?? '', '/');
        $username = $url['user'] ?? '';
        $password = $url['pass'] ?? '';

        if (empty($database)) {
            throw new \InvalidArgumentException('Database name is required in URL');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        return new self($dsn, $username, $password);
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
