<?php
declare(strict_types=1);

header('Content-Type: application/json');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use GripAndGrin\Infrastructure\Database\DatabaseConnection;

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? getenv('RAILWAY_ENVIRONMENT') ?? 'development',
    'database' => [
        'status' => 'unknown',
        'connection_time' => null,
        'error' => null
    ],
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'gd' => extension_loaded('gd'),
        'zip' => extension_loaded('zip')
    ]
];

// Check database connection
try {
    $startTime = microtime(true);

    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    if ($databaseUrl) {
        $db = DatabaseConnection::fromUrl($databaseUrl);
        $health['database']['connection_type'] = 'DATABASE_URL';
    } else {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'mysql';
        $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'grip_and_grin';
        $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'user';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'password';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $db = new DatabaseConnection($dsn, $username, $password);
        $health['database']['connection_type'] = 'individual_vars';
    }

    $pdo = $db->getConnection();
    $stmt = $pdo->query('SELECT VERSION() as version, NOW() as current_time');
    $result = $stmt->fetch();

    $endTime = microtime(true);

    $health['database']['status'] = 'connected';
    $health['database']['connection_time'] = round(($endTime - $startTime) * 1000, 2) . 'ms';
    $health['database']['mysql_version'] = $result['version'] ?? 'unknown';
    $health['database']['server_time'] = $result['current_time'] ?? 'unknown';

    // Check if tables exist
    $tables = ['users', 'categories', 'articles', 'comments'];
    $existingTables = [];

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $existingTables[] = $table;
        }
    }

    $health['database']['tables'] = $existingTables;
    $health['database']['migration_status'] = count($existingTables) === count($tables) ? 'complete' : 'incomplete';

} catch (Exception $e) {
    $health['status'] = 'degraded';
    $health['database']['status'] = 'disconnected';
    $health['database']['error'] = $e->getMessage();
}

// Add system information
$health['system'] = [
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown'
];

// Set appropriate HTTP status code
if ($health['status'] === 'healthy') {
    http_response_code(200);
} else {
    http_response_code(503);
}

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
