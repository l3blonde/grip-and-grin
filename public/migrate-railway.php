<?php
declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use GripAndGrin\Infrastructure\Database\DatabaseConnection;

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Grip & Grin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        .log {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 10px 5px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🎣</div>
        <div class="title">Database Migration</div>
        <div class="subtitle">Setting up Grip & Grin database</div>
    </div>

    <div class="log">
        <?php

        try {
            echo "🚀 Starting database migration...\n";
            echo "📅 " . date('Y-m-d H:i:s') . "\n\n";

            // Initialize database connection
            $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
            if ($databaseUrl) {
                echo "🔗 Using Railway DATABASE_URL\n";
                $db = DatabaseConnection::fromUrl($databaseUrl);
            } else {
                echo "🔗 Using individual environment variables\n";
                $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'mysql';
                $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'grip_and_grin';
                $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'user';
                $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'password';

                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                $db = new DatabaseConnection($dsn, $username, $password);
            }

            $pdo = $db->getConnection();
            echo "✅ Database connection established\n\n";

            // Create tables
            echo "📋 Creating database tables...\n";

            // Categories table
            $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
            echo "✅ Categories table created\n";

            // Users table
            $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            role ENUM('admin', 'editor', 'user') DEFAULT 'user',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
            echo "✅ Users table created\n";

            // Articles table
            $pdo->exec("
        CREATE TABLE IF NOT EXISTS articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            excerpt TEXT,
            content LONGTEXT NOT NULL,
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
            echo "✅ Articles table created\n";

            // Comments table
            $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id INT NOT NULL,
            author_name VARCHAR(100) NOT NULL,
            author_email VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            is_approved BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
            INDEX idx_article (article_id),
            INDEX idx_approved (is_approved)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
            echo "✅ Comments table created\n\n";

            // Insert sample data
            echo "📝 Inserting sample data...\n";

            // Insert categories
            $categories = [
                ['name' => 'Freshwater Fishing', 'slug' => 'freshwater', 'description' => 'Tips and stories about freshwater fishing'],
                ['name' => 'Saltwater Fishing', 'slug' => 'saltwater', 'description' => 'Ocean and saltwater fishing adventures'],
                ['name' => 'Fly Fishing', 'slug' => 'fly-fishing', 'description' => 'The art of fly fishing'],
                ['name' => 'Ice Fishing', 'slug' => 'ice-fishing', 'description' => 'Cold weather fishing techniques'],
                ['name' => 'Gear Reviews', 'slug' => 'gear-reviews', 'description' => 'Reviews of fishing equipment and gear'],
                ['name' => 'Fishing Stories', 'slug' => 'stories', 'description' => 'Personal fishing adventures and tales']
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
            foreach ($categories as $category) {
                $stmt->execute([$category['name'], $category['slug'], $category['description']]);
            }
            echo "✅ Sample categories inserted\n";

            // Insert admin user
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@gripandgrin.com', $adminPassword, 'Admin', 'User', 'admin']);
            echo "✅ Admin user created (username: admin, password: admin123)\n";

            // Insert sample user
            $userPassword = password_hash('user123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['fisherman', 'user@gripandgrin.com', $userPassword, 'John', 'Fisher', 'user']);
            echo "✅ Sample user created (username: fisherman, password: user123)\n";

            // Insert sample articles
            $articles = [
                [
                    'title' => 'Best Bass Fishing Spots in North America',
                    'slug' => 'best-bass-fishing-spots-north-america',
                    'excerpt' => 'Discover the top bass fishing locations across North America, from hidden gems to well-known hotspots.',
                    'content' => '<p>Bass fishing is one of the most popular forms of angling in North America, and for good reason. These aggressive predators provide exciting fights and can be found in a variety of water bodies across the continent.</p><p>In this comprehensive guide, we\'ll explore some of the best bass fishing spots that every angler should consider visiting...</p>',
                    'category_id' => 1,
                    'status' => 'published'
                ],
                [
                    'title' => 'Fly Fishing for Beginners: Essential Techniques',
                    'slug' => 'fly-fishing-beginners-essential-techniques',
                    'excerpt' => 'Learn the fundamental techniques of fly fishing with this comprehensive beginner\'s guide.',
                    'content' => '<p>Fly fishing is often considered the most artistic form of angling. It requires patience, skill, and an understanding of both the fish and their environment.</p><p>This guide will walk you through the essential techniques every beginner needs to know...</p>',
                    'category_id' => 3,
                    'status' => 'published'
                ],
                [
                    'title' => 'Top 10 Saltwater Fishing Destinations',
                    'slug' => 'top-10-saltwater-fishing-destinations',
                    'excerpt' => 'Explore the world\'s premier saltwater fishing destinations and what makes each location special.',
                    'content' => '<p>Saltwater fishing offers some of the most exciting angling experiences in the world. From battling massive marlin to catching delicious snapper, the ocean provides endless opportunities.</p><p>Here are our top 10 saltwater fishing destinations...</p>',
                    'category_id' => 2,
                    'status' => 'published'
                ],
                [
                    'title' => 'Ice Fishing Safety: Essential Tips for Cold Weather Angling',
                    'slug' => 'ice-fishing-safety-essential-tips',
                    'excerpt' => 'Stay safe on the ice with these essential ice fishing safety tips and techniques.',
                    'content' => '<p>Ice fishing can be one of the most rewarding forms of angling, but it also comes with unique risks. Safety should always be your top priority when venturing onto frozen water.</p><p>Here are the essential safety tips every ice angler should know...</p>',
                    'category_id' => 4,
                    'status' => 'published'
                ],
                [
                    'title' => 'Review: Best Fishing Reels Under $100',
                    'slug' => 'review-best-fishing-reels-under-100',
                    'excerpt' => 'Our comprehensive review of the best fishing reels you can buy for under $100.',
                    'content' => '<p>Finding a quality fishing reel that won\'t break the bank can be challenging. With so many options available, it\'s important to know what features to look for and which brands offer the best value.</p><p>We\'ve tested dozens of reels to bring you this comprehensive review...</p>',
                    'category_id' => 5,
                    'status' => 'published'
                ],
                [
                    'title' => 'The One That Got Away: A Lake Superior Adventure',
                    'slug' => 'the-one-that-got-away-lake-superior-adventure',
                    'excerpt' => 'A thrilling fishing story about an epic battle with a monster lake trout on Lake Superior.',
                    'content' => '<p>Every angler has a story about "the one that got away," but this particular tale from Lake Superior will stay with me forever. It was a crisp October morning when my fishing partner and I set out...</p><p>The lake was calm, almost glass-like, as we made our way to a promising drop-off...</p>',
                    'category_id' => 6,
                    'status' => 'published'
                ]
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO articles (title, slug, excerpt, content, category_id, author_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            foreach ($articles as $article) {
                $stmt->execute([
                    $article['title'],
                    $article['slug'],
                    $article['excerpt'],
                    $article['content'],
                    $article['category_id'],
                    1, // admin user ID
                    $article['status']
                ]);
            }
            echo "✅ Sample articles inserted\n\n";

            echo "🎉 Database migration completed successfully!\n";
            echo "📊 Database is ready for use\n";
            echo "🔗 You can now visit your application homepage\n";

            $migrationSuccess = true;

        } catch (Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
            $migrationSuccess = false;
        }

        ?>
    </div>

    <?php if (isset($migrationSuccess) && $migrationSuccess): ?>
        <div class="success">
            <strong>✅ Migration Successful!</strong><br>
            Your database has been set up with sample data. You can now use your application.
            <br><br>
            <strong>Admin Login:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code>
        </div>
        <div style="text-align: center;">
            <a href="/" class="btn btn-success">Visit Homepage</a>
            <a href="/health.php" class="btn">Health Check</a>
        </div>
    <?php else: ?>
        <div class="error">
            <strong>❌ Migration Failed</strong><br>
            Please check the error messages above and try again.
        </div>
        <div style="text-align: center;">
            <a href="/migrate-railway.php" class="btn">Retry Migration</a>
            <a href="/health.php" class="btn">Health Check</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
