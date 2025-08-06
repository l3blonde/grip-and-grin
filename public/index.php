<?php
declare(strict_types=1);

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Check if database is available
function isDatabaseAvailable(): bool {
    try {
        // Try Railway DATABASE_URL first
        $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        if ($databaseUrl) {
            $pdo = new PDO($databaseUrl, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $pdo->query('SELECT 1');
            return true;
        }

        // Fallback to individual environment variables
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'mysql';
        $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'grip_and_grin';
        $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'user';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'password';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// If database is not available, show setup page
if (!isDatabaseAvailable()) {
    $phpVersion = PHP_VERSION;
    $environment = $_ENV['RAILWAY_ENVIRONMENT'] ?? getenv('RAILWAY_ENVIRONMENT') ?? 'Development';
    $port = $_ENV['PORT'] ?? getenv('PORT') ?? '80';
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Grip & Grin - Setup Required</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .setup-container {
                background: white;
                border-radius: 12px;
                padding: 40px;
                max-width: 800px;
                width: 100%;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            .logo {
                text-align: center;
                font-size: 3rem;
                margin-bottom: 10px;
            }
            .title {
                text-align: center;
                font-size: 2rem;
                font-weight: bold;
                color: #333;
                margin-bottom: 10px;
            }
            .subtitle {
                text-align: center;
                color: #666;
                margin-bottom: 30px;
                font-size: 1.1rem;
            }
            .status {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                color: #856404;
            }
            .status h3 {
                margin-bottom: 10px;
                font-size: 1.2rem;
            }
            .info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .info-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                border-left: 4px solid #007cba;
            }
            .info-card h4 {
                margin-bottom: 15px;
                color: #333;
            }
            .info-card ul {
                list-style: none;
                padding: 0;
            }
            .info-card li {
                padding: 5px 0;
                display: flex;
                justify-content: space-between;
                border-bottom: 1px solid #e9ecef;
            }
            .info-card li:last-child {
                border-bottom: none;
            }
            .status-indicator {
                font-weight: bold;
            }
            .status-ok { color: #28a745; }
            .status-error { color: #dc3545; }
            .status-warning { color: #ffc107; }
            .instructions {
                background: #e8f5e8;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                color: #155724;
            }
            .instructions h4 {
                margin-bottom: 15px;
            }
            .instructions ol {
                padding-left: 20px;
            }
            .instructions li {
                margin: 10px 0;
                line-height: 1.5;
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
            .btn-secondary {
                background: #6c757d;
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
    <div class="setup-container">
        <div class="logo">🎣</div>
        <div class="title">Grip & Grin</div>
        <div class="subtitle">Fishing Blog Platform</div>

        <div class="status">
            <h3>⚠️ Database Setup Required</h3>
            <p>Your PHP application is running successfully, but the database connection needs to be configured.</p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h4>📊 System Status</h4>
                <ul>
                    <li>
                        <span>Environment:</span>
                        <span class="status-indicator status-ok"><?= htmlspecialchars($environment) ?></span>
                    </li>
                    <li>
                        <span>PHP Version:</span>
                        <span class="status-indicator status-ok"><?= htmlspecialchars($phpVersion) ?></span>
                    </li>
                    <li>
                        <span>Port:</span>
                        <span class="status-indicator status-ok"><?= htmlspecialchars($port) ?></span>
                    </li>
                    <li>
                        <span>Apache:</span>
                        <span class="status-indicator status-ok">Running</span>
                    </li>
                    <li>
                        <span>Database:</span>
                        <span class="status-indicator status-error">Not Connected</span>
                    </li>
                </ul>
            </div>

            <div class="info-card">
                <h4>🔧 Configuration</h4>
                <ul>
                    <li>
                        <span>DATABASE_URL:</span>
                        <span class="status-indicator <?= $databaseUrl ? 'status-ok' : 'status-error' ?>">
                                <?= $databaseUrl ? 'Set' : 'Not Set' ?>
                            </span>
                    </li>
                    <li>
                        <span>Composer:</span>
                        <span class="status-indicator status-ok">Loaded</span>
                    </li>
                    <li>
                        <span>Autoloader:</span>
                        <span class="status-indicator status-ok">Active</span>
                    </li>
                    <li>
                        <span>Extensions:</span>
                        <span class="status-indicator status-ok">PDO, MySQL</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="instructions">
            <h4>🚀 Railway Deployment Steps</h4>
            <ol>
                <li><strong>Add MySQL Database Service:</strong><br>
                    Go to Railway Dashboard → Add Service → Database → MySQL
                </li>
                <li><strong>Wait for DATABASE_URL:</strong><br>
                    Railway will automatically inject the DATABASE_URL environment variable
                </li>
                <li><strong>Run Database Migration:</strong><br>
                    Visit <code>/migrate-railway.php</code> to set up tables and sample data
                </li>
                <li><strong>Refresh This Page:</strong><br>
                    The full application will load automatically once database is connected
                </li>
            </ol>
        </div>

        <div style="text-align: center;">
            <a href="/health.php" class="btn btn-secondary">Health Check</a>
            <a href="/migrate-railway.php" class="btn">Setup Database</a>
        </div>

        <div class="footer">
            <p><strong>Grip & Grin</strong> - Built with PHP <?= htmlspecialchars($phpVersion) ?></p>
            <p>Ready for Railway deployment with MySQL database</p>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Database is available - load full application
try {
    use GripAndGrin\Infrastructure\Database\DatabaseConnection;
    use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
    use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
    use GripAndGrin\Infrastructure\Repositories\PDOUserRepository;
    use GripAndGrin\Application\UseCases\GetPaginatedArticlesUseCase;
    use GripAndGrin\Application\UseCases\GetCategoriesUseCase;
    use GripAndGrin\Infrastructure\Services\SessionService;
    use Twig\Environment;
    use Twig\Loader\FilesystemLoader;

    // Initialize database connection
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    if ($databaseUrl) {
        $db = DatabaseConnection::fromUrl($databaseUrl);
    } else {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'mysql';
        $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'grip_and_grin';
        $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'user';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'password';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $db = new DatabaseConnection($dsn, $username, $password);
    }

    // Initialize repositories
    $articleRepository = new PDOArticleRepository($db);
    $categoryRepository = new PDOCategoryRepository($db);
    $userRepository = new PDOUserRepository($db);

    // Initialize services
    $sessionService = new SessionService();

    // Initialize Twig
    $loader = new FilesystemLoader(__DIR__ . '/../templates');
    $twig = new Environment($loader, [
        'cache' => false,
        'debug' => true,
    ]);

    // Add session globals to Twig
    $twig->addGlobal('session', $sessionService);

    // Register custom Twig extensions
    try {
        $twig->addExtension(new \GripAndGrin\Infrastructure\Twig\SvgIconExtension());
    } catch (Exception $e) {
        error_log('Failed to register SvgIconExtension: ' . $e->getMessage());
    }

    // Simple routing
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($path) {
        case '/':
        case '/home':
            // Home page with articles
            $getArticlesUseCase = new GetPaginatedArticlesUseCase($articleRepository);
            $getCategoriesUseCase = new GetCategoriesUseCase($categoryRepository);

            $page = (int)($_GET['page'] ?? 1);
            $limit = 6;

            $result = $getArticlesUseCase->execute($page, $limit);
            $categories = $getCategoriesUseCase->execute();

            echo $twig->render('home.html.twig', [
                'title' => 'Grip & Grin - Fishing Adventures',
                'articles' => $result['articles'],
                'categories' => $categories,
                'pagination' => $result['pagination'],
                'current_page' => $page,
            ]);
            break;

        case '/health':
        case '/health.php':
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'healthy',
                'database' => 'connected',
                'php_version' => PHP_VERSION,
                'timestamp' => date('c'),
                'memory_usage' => memory_get_usage(true)
            ]);
            break;

        default:
            http_response_code(404);
            echo $twig->render('404.html.twig', [
                'title' => 'Page Not Found - Grip & Grin'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(500);

    // Show error page
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Application Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
            .error { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error h1 { color: #dc3545; margin-bottom: 20px; }
            .error pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>Application Error</h1>
            <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
        </div>
    </body>
    </html>';
}
?>
