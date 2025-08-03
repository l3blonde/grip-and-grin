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

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO($this->dsn, $this->username, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // In a real app, use a logger
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
