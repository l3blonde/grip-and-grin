<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

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

// Services
$container[ImageService::class] = new ImageService();

// Twig with session globals
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$container['twig'] = new Environment($loader);
$container['twig']->addGlobal('session', $container[SessionService::class]);

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
} elseif ($path === '/profile') {
    $response = $container[ProfileController::class]->show();
} elseif ($path === '/profile/edit') {
    $response = $container[ProfileController::class]->edit();
} elseif ($path === '/profile/update') {
    $response = $container[ProfileController::class]->update($request);
} elseif ($path === '/admin/dashboard') {
    $response = $container[AdminController::class]->dashboard();
} elseif ($path === '/admin/articles') {
    $response = $container[AdminController::class]->articles($request);
} elseif ($path === '/admin/articles/create') {
    if ($request->getMethod() === 'POST') {
        $response = $container[AdminController::class]->createArticle($request);
    } else {
        $response = $container[AdminController::class]->createArticleForm();
    }
} elseif (preg_match('/^\/admin\/articles\/(\d+)\/edit$/', $path, $matches)) {
    $articleId = (int) $matches[1];
    $response = $container[AdminController::class]->editArticleForm($articleId);
} elseif (preg_match('/^\/admin\/articles\/(\d+)\/update$/', $path, $matches)) {
    $articleId = (int) $matches[1];
    $response = $container[AdminController::class]->updateArticle($articleId, $request);
} elseif (preg_match('/^\/admin\/articles\/(\d+)\/delete$/', $path, $matches)) {
    $articleId = (int) $matches[1];
    $response = $container[AdminController::class]->deleteArticle($articleId, $request);
} elseif ($path === '/admin/users') {
    $response = $container[AdminController::class]->users();
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
