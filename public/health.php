<?php
header('Content-Type: application/json');

$health = [
    'status' => 'OK',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'uptime' => $_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME_FLOAT'],
    'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'development'
];

// Test database connection if available
try {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    if ($databaseUrl) {
        $url = parse_url($databaseUrl);
        $dsn = "mysql:host={$url['host']};port=" . ($url['port'] ?? 3306) . ";dbname=" . ltrim($url['path'], '/') . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $url['user'], $url['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $health['database'] = 'connected';
    } else {
        $health['database'] = 'not_configured';
    }
} catch (Exception $e) {
    $health['database'] = 'error: ' . $e->getMessage();
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
