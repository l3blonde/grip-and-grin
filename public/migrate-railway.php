<?php
declare(strict_types=1);

// Railway Database Migration Script
// Access this at: https://your-domain.com/migrate-railway.php
// DELETE THIS FILE AFTER RUNNING!

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\DatabaseConnection;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>';

echo "<h1>🚂 Railway Database Migration</h1>";
echo "<div class='info'>Starting migration process...</div>";

try {
    // Get Railway environment variables
    $databaseUrl = getenv('DATABASE_URL') ?: $_ENV['DATABASE_URL'] ?? null;

    if (!$databaseUrl) {
        // Try individual variables
        $host = getenv('MYSQL_HOST') ?: $_ENV['MYSQL_HOST'] ?? 'localhost';
        $port = getenv('MYSQL_PORT') ?: $_ENV['MYSQL_PORT'] ?? '3306';
        $database = getenv('MYSQL_DATABASE') ?: $_ENV['MYSQL_DATABASE'] ?? 'railway';
        $username = getenv('MYSQL_USER') ?: $_ENV['MYSQL_USER'] ?? 'root';
        $password = getenv('MYSQL_PASSWORD') ?: $_ENV['MYSQL_PASSWORD'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        echo "<div class='info'>Using individual environment variables</div>";
        echo "<div class='info'>Host: {$host}:{$port}, Database: {$database}</div>";
    } else {
        // Parse DATABASE_URL (format: mysql://user:pass@host:port/db)
        $parsed = parse_url($databaseUrl);
        $host = $parsed['host'];
        $port = $parsed['port'] ?? 3306;
        $database = ltrim($parsed['path'], '/');
        $username = $parsed['user'];
        $password = $parsed['pass'];

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        echo "<div class='info'>Using DATABASE_URL</div>";
        echo "<div class='info'>Host: {$host}:{$port}, Database: {$database}</div>";
    }

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<div class='success'>✅ Database connection successful!</div>";

    // Create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        content TEXT NOT NULL,
        excerpt TEXT,
        featured_image VARCHAR(255),
        category_id INT,
        author_id INT,
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_published_at (published_at),
        INDEX idx_category (category_id)
    );
    ";

    $pdo->exec($sql);
    echo "<div class='success'>✅ Tables created successfully!</div>";

    // Insert sample categories
    $categories = [
        ['name' => 'All', 'slug' => 'all', 'description' => 'All articles'],
        ['name' => 'Classics', 'slug' => 'classics', 'description' => 'Classic firearms and collectibles'],
        ['name' => 'Collector', 'slug' => 'collector', 'description' => 'Collector items and rare finds'],
        ['name' => 'Reviews', 'slug' => 'reviews', 'description' => 'Product reviews and comparisons'],
        ['name' => 'Trends', 'slug' => 'trends', 'description' => 'Latest trends and news']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category['name'], $category['slug'], $category['description']]);
    }
    echo "<div class='success'>✅ Sample categories inserted!</div>";

    // Create admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@gripandgrin.org', $adminPassword, 'admin']);
    echo "<div class='success'>✅ Admin user created! (username: admin, password: admin123)</div>";

    // Insert sample articles
    $sampleArticles = [
        [
            'title' => 'Welcome to Grip and Grin',
            'slug' => 'welcome-to-grip-and-grin',
            'content' => 'Welcome to our firearms and outdoor community! This is your go-to place for reviews, collecting tips, and the latest trends in the shooting sports world.',
            'excerpt' => 'Welcome to our firearms and outdoor community!',
            'category_id' => 1,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Classic Firearms: A Collector\'s Guide',
            'slug' => 'classic-firearms-collectors-guide',
            'content' => 'Collecting classic firearms is both an art and a science. In this comprehensive guide, we\'ll explore what makes a firearm collectible and how to start your collection.',
            'excerpt' => 'A comprehensive guide to collecting classic firearms.',
            'category_id' => 2,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'title' => '2024 Shooting Sports Trends',
            'slug' => '2024-shooting-sports-trends',
            'content' => 'The shooting sports industry continues to evolve. Here are the top trends we\'re seeing in 2024, from new technologies to changing demographics.',
            'excerpt' => 'Top shooting sports trends for 2024.',
            'category_id' => 5,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO articles (title, slug, content, excerpt, category_id, author_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sampleArticles as $article) {
        $stmt->execute([
            $article['title'],
            $article['slug'],
            $article['content'],
            $article['excerpt'],
            $article['category_id'],
            1, // admin user ID
            $article['status'],
            $article['published_at']
        ]);
    }
    echo "<div class='success'>✅ Sample articles inserted!</div>";

    echo "<h2>🎉 Migration Complete!</h2>";
    echo "<div class='error'><strong>IMPORTANT:</strong> Delete this file (migrate-railway.php) for security!</div>";
    echo "<p><a href='/'>Go to Homepage</a></p>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='error'>Stack trace:</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo '</body></html>';
?>
