<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use GripAndGrin\Application\UseCases\AuthenticateUserUseCase;
use GripAndGrin\Application\UseCases\GetArticleBySlugUseCase;
use GripAndGrin\Application\UseCases\GetArticlesUseCase;
use GripAndGrin\Application\UseCases\GetArticlesByCategoryUseCase;
use GripAndGrin\Application\UseCases\GetCategoriesUseCase;
use GripAndGrin\Application\UseCases\GetPaginatedArticlesUseCase;
use GripAndGrin\Application\UseCases\RegisterUserUseCase;
use GripAndGrin\Application\UseCases\SearchArticlesUseCase;
use GripAndGrin\Infrastructure\Database\DatabaseConnection;
use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
use GripAndGrin\Infrastructure\Repositories\PDOUserRepository;
use GripAndGrin\Infrastructure\Services\SessionService;
use GripAndGrin\Presentation\Controllers\ArticleController;
use GripAndGrin\Presentation\Controllers\AuthController;
use GripAndGrin\Presentation\Controllers\CategoryController;
use GripAndGrin\Presentation\Controllers\HomeController;
use GripAndGrin\Presentation\Controllers\SearchController;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Basic dependency injection container
$container = [];

// Database Connection
$container[DatabaseConnection::class] = new DatabaseConnection(
    'mysql:host=mysql;dbname=grip_and_grin_db;charset=utf8mb4',
    'user',
    'password'
);

// Session Service
$container[SessionService::class] = new SessionService();

// Twig with session globals
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$container['twig'] = new Environment($loader);
$container['twig']->addGlobal('session', $container[SessionService::class]);

// Repositories
$container[PDOArticleRepository::class] = new PDOArticleRepository($container[DatabaseConnection::class]);
$container[PDOCategoryRepository::class] = new PDOCategoryRepository($container[DatabaseConnection::class]);
$container[PDOUserRepository::class] = new PDOUserRepository($container[DatabaseConnection::class]);

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

// Simple Router
$request = Request::createFromGlobals();
$path = $request->getPathInfo();
$parts = explode('/', trim($path, '/'));

if ($path === '/' || $path === '') {
    $response = $container[HomeController::class]->show($request);
} elseif ($path === '/search') {
    $response = $container[SearchController::class]->search($request);
} elseif ($path === '/login') {
    if ($request->getMethod() === 'POST') {
        $response = $container[AuthController::class]->login($request);
    } else {
        $response = $container[AuthController::class]->showLogin();
    }
} elseif ($path === '/register') {
    if ($request->getMethod() === 'POST') {
        $response = $container[AuthController::class]->register($request);
    } else {
        $response = $container[AuthController::class]->showRegister();
    }
} elseif ($path === '/logout') {
    $response = $container[AuthController::class]->logout();
} elseif (count($parts) === 2 && $parts[0] === 'category') {
    $categorySlug = $parts[1];
    $response = $container[CategoryController::class]->show($categorySlug, $request);
} elseif (count($parts) === 2 && $parts[0] === 'article') {
    $slug = $parts[1];
    $response = $container[ArticleController::class]->show($slug);
} else {
    http_response_code(404);
    echo $container['twig']->render('404.html.twig');
    exit;
}

$response->send();
