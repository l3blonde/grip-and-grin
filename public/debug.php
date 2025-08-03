<?php
declare(strict_types=1);

echo "<h1>Debug Information</h1>";

// Check if autoloader exists
echo "<h2>Autoloader Check</h2>";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "✅ Autoloader file exists<br>";
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader loaded successfully<br>";
} else {
    echo "❌ Autoloader file missing<br>";
}

// Check if classes can be loaded
echo "<h2>Class Loading Check</h2>";
try {
    $reflection = new ReflectionClass('GripAndGrin\Application\UseCases\GetArticlesUseCase');
    echo "✅ GetArticlesUseCase class found at: " . $reflection->getFileName() . "<br>";
} catch (Exception $e) {
    echo "❌ GetArticlesUseCase class not found: " . $e->getMessage() . "<br>";
}

try {
    $reflection = new ReflectionClass('GripAndGrin\Domain\Entities\Article');
    echo "✅ Article entity found at: " . $reflection->getFileName() . "<br>";
} catch (Exception $e) {
    echo "❌ Article entity not found: " . $e->getMessage() . "<br>";
}

// Check database connection
echo "<h2>Database Connection Check</h2>";
try {
    $pdo = new PDO(
        'mysql:host=mysql;dbname=grip_and_grin_db;charset=utf8mb4',
        'user',
        'password'
    );
    echo "✅ Database connection successful<br>";

    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles");
    $result = $stmt->fetch();
    echo "✅ Found " . $result['count'] . " articles in database<br>";

} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Show PHP info
echo "<h2>PHP Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Include path: " . get_include_path() . "<br>";

// Show file structure
echo "<h2>File Structure Check</h2>";
echo "Files in /var/www/html/src/Application/UseCases/:<br>";
if (is_dir('/var/www/html/src/Application/UseCases/')) {
    $files = scandir('/var/www/html/src/Application/UseCases/');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- " . $file . "<br>";
        }
    }
} else {
    echo "❌ Directory does not exist<br>";
}
