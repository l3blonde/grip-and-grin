<?php

declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Load environment variables from .env file
            self::loadEnv();

            $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
            $dbName = $_ENV['DB_NAME'] ?? 'grip_and_grin_db';
            $dbUser = $_ENV['DB_USER'] ?? 'root';
            $dbPassword = (isset($_ENV['DB_PASS']) && $_ENV['DB_PASS'] !== '') ? $_ENV['DB_PASS'] : null;
            $dbPort = $_ENV['DB_PORT'] ?? '3306';

            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

            error_log("[DEBUG] DatabaseConnection attempting connection with user: $dbUser, host: $dbHost, db: $dbName");
            error_log("[DEBUG] Password is " . ($dbPassword === null ? "NULL (no password)" : "SET (length: " . strlen($dbPassword) . ")"));
            error_log("[DEBUG] Raw DB_PASS from env: '" . ($_ENV['DB_PASS'] ?? 'NOT_SET') . "'");

            try {
                if ($dbPassword === null) {
                    self::$instance = new PDO($dsn, $dbUser, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } else {
                    self::$instance = new PDO($dsn, $dbUser, $dbPassword, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                }
                error_log("[DEBUG] DatabaseConnection successful");
            } catch (PDOException $e) {
                error_log("[ERROR] Database connection failed: " . $e->getMessage());
                error_log("[ERROR] DSN: $dsn, User: $dbUser");
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private static function loadEnv(): void
    {
        $envFile = __DIR__ . '/../../../.env';

        error_log("[DEBUG] Looking for .env file at: $envFile");

        if (file_exists($envFile)) {
            error_log("[DEBUG] .env file found, loading variables");
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue; // Skip comments
                }

                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $_ENV[trim($name)] = trim($value);
                    error_log("[DEBUG] Loaded env var: " . trim($name) . " = " . trim($value));
                }
            }
        } else {
            error_log("[WARNING] .env file not found at: $envFile");
        }
    }

    public function getConnection(): PDO
    {
        return self::getInstance();
    }
}
