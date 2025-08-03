<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use GripAndGrin\Application\UseCases\GetArticleBySlugUseCase;
use GripAndGrin\Application\UseCases\GetArticlesUseCase;
use GripAndGrin\Infrastructure\Database\DatabaseConnection;
use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use GripAndGrin\Presentation\Controllers\ArticleController;
use GripAndGrin\Presentation\Controllers\HomeController;
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

// Twig
$container['twig'] = new Environment(new FilesystemLoader(__DIR__ . '/../templates'));

// Repositories
$container[PDOArticleRepository::class] = new PDOArticleRepository($container[DatabaseConnection::class]);

// Use Cases
$container[GetArticlesUseCase::class] = new GetArticlesUseCase($container[PDOArticleRepository::class]);
$container[GetArticleBySlugUseCase::class] = new GetArticleBySlugUseCase($container[PDOArticleRepository::class]);

// Controllers
$container[HomeController::class] = new HomeController($container['twig'], $container[GetArticlesUseCase::class]);
$container[ArticleController::class] = new ArticleController($container['twig'], $container[GetArticleBySlugUseCase::class]);

// Simple Router
$request = Request::createFromGlobals();
$path = $request->getPathInfo();
$parts = explode('/', trim($path, '/'));

if ($path === '/' || $path === '') {
    $response = $container[HomeController::class]->show();
} elseif (count($parts) === 2 && $parts[0] === 'article') {
    $slug = $parts[1];
    $response = $container[ArticleController::class]->show($slug);
} else {
    http_response_code(404);
    echo $container['twig']->render('404.html.twig');
    exit;
}

$response->send();
