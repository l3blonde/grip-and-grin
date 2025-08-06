<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $username,
        private readonly string $password
    ) {}

    public static function fromUrl(string $databaseUrl): self
    {
        $url = parse_url($databaseUrl);

        $host = $url['host'];
        $port = $url['port'] ?? 3306;
        $database = ltrim($url['path'], '/');
        $username = $url['user'];
        $password = $url['pass'];

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        return new self($dsn, $username, $password);
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO($this->dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]);
            } catch (PDOException $e) {
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
