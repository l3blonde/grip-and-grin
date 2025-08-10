<?php
// Health check endpoint for DigitalOcean App Platform

header('Content-Type: application/json');

try {
    // Check if basic files exist
    $requiredFiles = [
        __DIR__ . '/index.php',
        __DIR__ . '/../src/Application/UseCases/GetArticlesUseCase.php',
        __DIR__ . '/../templates/base.html.twig'
    ];

    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file missing: " . basename($file));
        }
    }

    // Check database connection if environment variables are set
    if (getenv('DATABASE_HOST')) {
        $host = getenv('DATABASE_HOST');
        $port = getenv('DATABASE_PORT') ?: 3306;
        $dbname = getenv('DATABASE_NAME');
        $username = getenv('DATABASE_USER');
        $password = getenv('DATABASE_PASSWORD');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Simple query to test connection
        $stmt = $pdo->query('SELECT 1');
        $result = $stmt->fetch();

        if (!$result) {
            throw new Exception("Database query failed");
        }
    }

    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'environment' => getenv('APP_ENV') ?: 'unknown'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
