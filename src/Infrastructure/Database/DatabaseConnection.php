<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private PDO $connection;

    public function __construct(?string $dsn = null, ?string $username = null, ?string $password = null)
    {
        // Railway environment variables
        if (!$dsn) {
            $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

            if ($databaseUrl) {
                // Parse DATABASE_URL (Railway format: mysql://user:pass@host:port/db)
                $parsed = parse_url($databaseUrl);
                $host = $parsed['host'];
                $port = $parsed['port'] ?? 3306;
                $database = ltrim($parsed['path'], '/');
                $username = $parsed['user'];
                $password = $parsed['pass'];

                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            } else {
                // Fallback to individual environment variables
                $host = $_ENV['MYSQL_HOST'] ?? 'mysql';
                $port = $_ENV['MYSQL_PORT'] ?? '3306';
                $database = $_ENV['MYSQL_DATABASE'] ?? 'grip_and_grin_db';
                $username = $_ENV['MYSQL_USER'] ?? 'user';
                $password = $_ENV['MYSQL_PASSWORD'] ?? 'password';

                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            }
        }

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new PDOException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
