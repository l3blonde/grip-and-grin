<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: text/html; charset=utf-8');

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Grip and Grin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 { 
            color: #2c3e50; 
            text-align: center; 
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        .log { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6;
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏎️ Database Migration</h1>
        <div class="log">
HTML;

try {
    echo "<span class='info'>Starting database migration...</span>\n";

    // Get database connection
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

    if (!$databaseUrl) {
        throw new Exception('DATABASE_URL environment variable not found');
    }

    echo "<span class='info'>Found DATABASE_URL environment variable</span>\n";

    // Parse database URL
    $url = parse_url($databaseUrl);
    $host = $url['host'];
    $port = $url['port'] ?? 3306;
    $database = ltrim($url['path'], '/');
    $username = $url['user'];
    $password = $url['pass'];

    echo "<span class='info'>Connecting to database: {$host}:{$port}/{$database}</span>\n";

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<span class='success'>✅ Database connection established</span>\n";

    // Create tables
    echo "<span class='info'>Creating database tables...</span>\n";

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✅ Users table created</span>\n";

    // Categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✅ Categories table created</span>\n";

    // Articles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            featured_image VARCHAR(255),
            category_id INT,
            author_id INT,
            status ENUM('draft', 'published') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<span class='success'>✅ Articles table created</span>\n";

    // Insert default categories
    echo "<span class='info'>Inserting default categories...</span>\n";

    $categories = [
        ['name' => 'Classics', 'slug' => 'classics', 'description' => 'Classic car reviews and stories'],
        ['name' => 'Collector', 'slug' => 'collector', 'description' => 'Collector car insights and tips'],
        ['name' => 'Reviews', 'slug' => 'reviews', 'description' => 'In-depth car reviews'],
        ['name' => 'Trends', 'slug' => 'trends', 'description' => 'Latest automotive trends']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category['name'], $category['slug'], $category['description']]);
    }
    echo "<span class='success'>✅ Default categories inserted</span>\n";

    // Create admin user
    echo "<span class='info'>Creating admin user...</span>\n";

    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@gripandgrin.com', $adminPassword, 'admin']);
    echo "<span class='success'>✅ Admin user created (username: admin, password: admin123)</span>\n";

    // Insert sample articles
    echo "<span class='info'>Inserting sample articles...</span>\n";

    $sampleArticles = [
        [
            'title' => 'The Golden Age of American Muscle Cars',
            'slug' => 'golden-age-american-muscle-cars',
            'content' => 'The 1960s and 1970s marked the golden age of American muscle cars. During this era, Detroit automakers engaged in a horsepower war that produced some of the most iconic vehicles in automotive history...',
            'excerpt' => 'Exploring the legendary era of American muscle cars and their lasting impact on automotive culture.',
            'category_id' => 1,
            'status' => 'published'
        ],
        [
            'title' => '1973 Porsche 911 Carrera RS: A Detailed Review',
            'slug' => '1973-porsche-911-carrera-rs-review',
            'content' => 'The 1973 Porsche 911 Carrera RS stands as one of the most revered sports cars ever created. With its distinctive ducktail spoiler and lightweight construction...',
            'excerpt' => 'A comprehensive review of the iconic 1973 Porsche 911 Carrera RS and what makes it special.',
            'category_id' => 3,
            'status' => 'published'
        ],
        [
            'title' => 'Building Your First Classic Car Collection',
            'slug' => 'building-first-classic-car-collection',
            'content' => 'Starting a classic car collection can be both exciting and overwhelming. Here are expert tips to help you make smart decisions and build a collection you\'ll love...',
            'excerpt' => 'Expert advice for new collectors on building a meaningful classic car collection.',
            'category_id' => 2,
            'status' => 'published'
        ]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO articles (title, slug, content, excerpt, category_id, author_id, status) VALUES (?, ?, ?, ?, ?, 1, ?)");
    foreach ($sampleArticles as $article) {
        $stmt->execute([
            $article['title'],
            $article['slug'],
            $article['content'],
            $article['excerpt'],
            $article['category_id'],
            $article['status']
        ]);
    }
    echo "<span class='success'>✅ Sample articles inserted</span>\n";

    echo "<span class='success'>🎉 Database migration completed successfully!</span>\n";
    echo "<span class='info'>You can now go back to the homepage and use the full application.</span>\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ Migration failed: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "<span class='error'>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</span>\n";
}

echo <<<HTML
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="/" style="display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;">
                Go to Homepage
            </a>
        </div>
    </div>
</body>
</html>
HTML;
?>
