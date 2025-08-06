<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Simple Router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$uri = parse_url($requestUri, PHP_URL_PATH);

// Health check endpoint (no database needed)
if ($uri === '/health' || $uri === '/health.php') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'database_status' => 'pending'
    ]);
    exit;
}

// Check if database is available
$databaseAvailable = false;

try {
    // Try to connect to database
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

    if ($databaseUrl) {
        // Railway provides DATABASE_URL
        $url = parse_url($databaseUrl);
        $host = $url['host'];
        $port = $url['port'] ?? 3306;
        $database = ltrim($url['path'], '/');
        $username = $url['user'];
        $password = $url['pass'];
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    } else {
        // Local development or fallback
        $dsn = 'mysql:host=mysql;dbname=grip_and_grin_db;charset=utf8mb4';
        $username = 'user';
        $password = 'password';
    }

    // Test the connection
    $testPdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);

    $databaseAvailable = true;

} catch (Exception $e) {
    // Database not available - show setup page
    error_log('Database connection failed: ' . $e->getMessage());
    $databaseAvailable = false;
}

// If database is not available, show setup page
if (!$databaseAvailable) {
    renderSetupPage();
    exit;
}

// Database is available - proceed with full application
try {
    use GripAndGrin\Application\UseCases\AuthenticateUserUseCase;
    use GripAndGrin\Application\UseCases\CreateArticleUseCase;
    use GripAndGrin\Application\UseCases\GetAllUsersUseCase;
    use GripAndGrin\Application\UseCases\GetArticleBySlugUseCase;
    use GripAndGrin\Application\UseCases\GetArticlesUseCase;
    use GripAndGrin\Application\UseCases\GetArticlesByCategoryUseCase;
    use GripAndGrin\Application\UseCases\GetCategoriesUseCase;
    use GripAndGrin\Application\UseCases\GetPaginatedArticlesUseCase;
    use GripAndGrin\Application\UseCases\GetUserProfileUseCase;
    use GripAndGrin\Application\UseCases\RegisterUserUseCase;
    use GripAndGrin\Application\UseCases\SearchArticlesUseCase;
    use GripAndGrin\Application\UseCases\UpdateArticleUseCase;
    use GripAndGrin\Application\UseCases\UpdateUserProfileUseCase;
    use GripAndGrin\Infrastructure\Database\DatabaseConnection;
    use GripAndGrin\Infrastructure\Middleware\AdminMiddleware;
    use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
    use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
    use GripAndGrin\Infrastructure\Repositories\PDOUserRepository;
    use GripAndGrin\Infrastructure\Services\ImageService;
    use GripAndGrin\Infrastructure\Services\SessionService;
    use GripAndGrin\Infrastructure\Twig\SvgIconExtension;
    use GripAndGrin\Presentation\Controllers\AdminController;
    use GripAndGrin\Presentation\Controllers\ArticleController;
    use GripAndGrin\Presentation\Controllers\AuthController;
    use GripAndGrin\Presentation\Controllers\CategoryController;
    use GripAndGrin\Presentation\Controllers\HomeController;
    use GripAndGrin\Presentation\Controllers\ProfileController;
    use GripAndGrin\Presentation\Controllers\SearchController;
    use Symfony\Component\HttpFoundation\Request;
    use Twig\Environment;
    use Twig\Loader\FilesystemLoader;

    $container = [];

    // Database Connection
    if ($databaseUrl) {
        $container[DatabaseConnection::class] = DatabaseConnection::fromUrl($databaseUrl);
    } else {
        $container[DatabaseConnection::class] = new DatabaseConnection($dsn, $username, $password);
    }

    // Session Service
    $container[SessionService::class] = new SessionService();

    // Services
    $container[ImageService::class] = new ImageService();

    // Twig Environment Setup
    $loader = new FilesystemLoader(__DIR__ . '/../templates');
    $container['twig'] = new Environment($loader, [
        'debug' => true,
        'cache' => false,
        'auto_reload' => true
    ]);

    // Add session globals to Twig
    $container['twig']->addGlobal('session', $container[SessionService::class]);

    // Register the SvgIconExtension
    try {
        $container['twig']->addExtension(new SvgIconExtension());
    } catch (Exception $e) {
        error_log('Failed to register SvgIconExtension: ' . $e->getMessage());
    }

    // Repositories
    $container[PDOArticleRepository::class] = new PDOArticleRepository($container[DatabaseConnection::class]);
    $container[PDOCategoryRepository::class] = new PDOCategoryRepository($container[DatabaseConnection::class]);
    $container[PDOUserRepository::class] = new PDOUserRepository($container[DatabaseConnection::class]);

    // Middleware
    $container[AdminMiddleware::class] = new AdminMiddleware(
        $container[SessionService::class],
        $container[PDOUserRepository::class]
    );

    // Use Cases
    $container[GetArticlesUseCase::class] = new GetArticlesUseCase($container[PDOArticleRepository::class]);
    $container[GetArticleBySlugUseCase::class] = new GetArticleBySlugUseCase($container[PDOArticleRepository::class]);
    $container[GetPaginatedArticlesUseCase::class] = new GetPaginatedArticlesUseCase($container[PDOArticleRepository::class]);
    $container[SearchArticlesUseCase::class] = new SearchArticlesUseCase($container[PDOArticleRepository::class]);
    $container[GetCategoriesUseCase::class] = new GetCategoriesUseCase($container[PDOCategoryRepository::class]);
    $container[GetArticlesByCategoryUseCase::class] = new GetArticlesByCategoryUseCase(
        $container[PDOArticleRepository::class],
        $container[PDOCategoryRepository::class]
    );
    $container[AuthenticateUserUseCase::class] = new AuthenticateUserUseCase($container[PDOUserRepository::class]);
    $container[RegisterUserUseCase::class] = new RegisterUserUseCase($container[PDOUserRepository::class]);
    $container[CreateArticleUseCase::class] = new CreateArticleUseCase(
        $container[PDOArticleRepository::class],
        $container[ImageService::class]
    );
    $container[UpdateArticleUseCase::class] = new UpdateArticleUseCase(
        $container[PDOArticleRepository::class],
        $container[ImageService::class]
    );
    $container[GetAllUsersUseCase::class] = new GetAllUsersUseCase($container[PDOUserRepository::class]);
    $container[GetUserProfileUseCase::class] = new GetUserProfileUseCase($container[PDOUserRepository::class]);
    $container[UpdateUserProfileUseCase::class] = new UpdateUserProfileUseCase($container[PDOUserRepository::class]);

    // Controllers
    $container[HomeController::class] = new HomeController($container['twig'], $container[GetPaginatedArticlesUseCase::class]);
    $container[ArticleController::class] = new ArticleController($container['twig'], $container[GetArticleBySlugUseCase::class]);
    $container[SearchController::class] = new SearchController($container['twig'], $container[SearchArticlesUseCase::class]);
    $container[CategoryController::class] = new CategoryController($container['twig'], $container[GetArticlesByCategoryUseCase::class]);
    $container[AuthController::class] = new AuthController(
        $container['twig'],
        $container[AuthenticateUserUseCase::class],
        $container[RegisterUserUseCase::class],
        $container[SessionService::class]
    );
    $container[AdminController::class] = new AdminController(
        $container['twig'],
        $container[AdminMiddleware::class],
        $container[SessionService::class],
        $container[GetPaginatedArticlesUseCase::class],
        $container[PDOArticleRepository::class],
        $container[GetCategoriesUseCase::class],
        $container[CreateArticleUseCase::class],
        $container[UpdateArticleUseCase::class],
        $container[GetAllUsersUseCase::class]
    );
    $container[ProfileController::class] = new ProfileController(
        $container['twig'],
        $container[SessionService::class],
        $container[GetUserProfileUseCase::class],
        $container[UpdateUserProfileUseCase::class]
    );

    // Route handling
    switch ($uri) {
        case '/':
            $controller = $container[HomeController::class];
            $controller->index();
            break;

        case '/search':
            $controller = $container[SearchController::class];
            $controller->search();
            break;

        case '/login':
            $controller = $container[AuthController::class];
            if ($requestMethod === 'POST') {
                $controller->login();
            } else {
                $controller->showLogin();
            }
            break;

        case '/register':
            $controller = $container[AuthController::class];
            if ($requestMethod === 'POST') {
                $controller->register();
            } else {
                $controller->showRegister();
            }
            break;

        case '/logout':
            $controller = $container[AuthController::class];
            $controller->logout();
            break;

        case '/profile':
            $controller = $container[ProfileController::class];
            if ($requestMethod === 'POST') {
                $controller->update();
            } else {
                $controller->show();
            }
            break;

        case '/admin/dashboard':
            $controller = $container[AdminController::class];
            $controller->dashboard();
            break;

        case '/admin/articles':
            $controller = $container[AdminController::class];
            $controller->articles();
            break;

        case '/admin/articles/create':
            $controller = $container[AdminController::class];
            if ($requestMethod === 'POST') {
                $controller->createArticle();
            } else {
                $controller->showCreateArticle();
            }
            break;

        case '/admin/users':
            $controller = $container[AdminController::class];
            $controller->users();
            break;

        default:
            // Handle dynamic routes
            $parts = explode('/', trim($uri, '/'));

            if (count($parts) === 2 && $parts[0] === 'category') {
                $controller = $container[CategoryController::class];
                $controller->show($parts[1]);
            } elseif (count($parts) === 2 && $parts[0] === 'article') {
                $controller = $container[ArticleController::class];
                $controller->show($parts[1]);
            } elseif (count($parts) === 3 && $parts[0] === 'admin' && $parts[1] === 'articles') {
                $controller = $container[AdminController::class];
                if ($parts[2] === 'edit' && $requestMethod === 'POST') {
                    $controller->updateArticle($_GET['id'] ?? '');
                } elseif ($parts[2] === 'edit') {
                    $controller->showEditArticle($_GET['id'] ?? '');
                } elseif ($parts[2] === 'delete') {
                    $controller->deleteArticle($_GET['id'] ?? '');
                }
            } else {
                // 404 Not Found
                http_response_code(404);
                echo $container['twig']->render('404.html.twig');
            }
            break;
    }

} catch (Exception $e) {
    error_log('Application error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Show error page
    http_response_code(500);
    echo '<h1>Application Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

function renderSetupPage(): void
{
    $phpVersion = PHP_VERSION;
    $environment = $_ENV['RAILWAY_ENVIRONMENT'] ?? 'Development';
    $port = $_ENV['PORT'] ?? '80';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grip and Grin - Setup Required</title>
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .status { 
            background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 25px; 
            border-radius: 10px; 
            margin: 25px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .status h2 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .info-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #007cba;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        .info-card ul {
            list-style: none;
            padding: 0;
        }
        .info-card li {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-card li:last-child {
            border-bottom: none;
        }
        .info-card strong {
            color: #495057;
            display: inline-block;
            width: 140px;
        }
        .button { 
            display: inline-block; 
            background: linear-gradient(135deg, #007cba, #005a87); 
            color: white; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 10px 5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        .setup-steps {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .setup-steps h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        .setup-steps ol {
            padding-left: 20px;
        }
        .setup-steps li {
            margin: 10px 0;
            color: #155724;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
        }
        .footer p {
            margin: 5px 0;
        }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            h1 { font-size: 2rem; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏎️ Grip and Grin</h1>
        
        <div class="status">
            <h2>⚠️ Database Setup Required</h2>
            <p>Your PHP application is running successfully, but the database connection is not yet configured. Please follow the setup steps below.</p>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>📊 System Information</h3>
                <ul>
                    <li><strong>Environment:</strong> {$environment}</li>
                    <li><strong>PHP Version:</strong> {$phpVersion}</li>
                    <li><strong>Port:</strong> {$port}</li>
                    <li><strong>Status:</strong> ✅ Running</li>
                    <li><strong>Database:</strong> ❌ Not Connected</li>
                </ul>
            </div>
            
            <div class="info-card">
                <h3>🔧 Application Status</h3>
                <ul>
                    <li><strong>Docker:</strong> ✅ Running</li>
                    <li><strong>Apache:</strong> ✅ Active</li>
                    <li><strong>PHP:</strong> ✅ Operational</li>
                    <li><strong>Composer:</strong> ✅ Loaded</li>
                    <li><strong>Database:</strong> ⏳ Pending Setup</li>
                </ul>
            </div>
        </div>
        
        <div class="setup-steps">
            <h3>📋 Next Steps to Complete Setup</h3>
            <ol>
                <li><strong>Add MySQL Database Service</strong> in Railway dashboard</li>
                <li><strong>Wait for Database URL</strong> to be automatically injected</li>
                <li><strong>Run Database Migration</strong> at <a href="/migrate-railway.php" class="button">Run Migration</a></li>
                <li><strong>Refresh this page</strong> - the full application will load automatically</li>
            </ol>
        </div>
        
        <div class="info-card">
            <h3>🔗 Available Endpoints</h3>
            <ul>
                <li><a href="/health" style="color: #007cba; text-decoration: none;">Health Check API</a> - JSON status endpoint</li>
                <li><a href="/migrate-railway.php" style="color: #007cba; text-decoration: none;">Database Migration</a> - Run after adding MySQL service</li>
            </ul>
        </div>
        
        <div class="footer">
            <p><strong>Grip and Grin</strong> - Classic Car News & Reviews Platform</p>
            <p>Built with PHP {$phpVersion} • Powered by Railway • Docker Containerized</p>
            <p>© 2025 Grip and Grin. Ready for database setup.</p>
        </div>
    </div>
</body>
</html>
HTML;
}
?>
