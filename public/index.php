<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use GripAndGrin\Infrastructure\Database\DatabaseConnection;
use GripAndGrin\Presentation\Controllers\HomeController;
use GripAndGrin\Presentation\Controllers\ArticleController;
use GripAndGrin\Presentation\Controllers\CategoryController;
use GripAndGrin\Presentation\Controllers\SearchController;
use GripAndGrin\Presentation\Controllers\AuthController;
use GripAndGrin\Presentation\Controllers\AdminController;
use GripAndGrin\Presentation\Controllers\ProfileController;
use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
use GripAndGrin\Application\UseCases\SearchArticlesUseCase;
use GripAndGrin\Presentation\Middleware\AdminMiddleware;

// Get database connection (returns PDO)
$db = DatabaseConnection::getInstance();

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'cache' => __DIR__ . '/../cache/twig',
    'auto_reload' => true
]);

$twig->addGlobal('session', $_SESSION ?? []);
$twig->addGlobal('user_id', $_SESSION['user_id'] ?? null);

// Get the current path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    switch (true) {
        case $path === '/' || $path === '/home':
            $controller = new HomeController($db);
            $data = $controller->index();
            echo $twig->render('home.html.twig', $data);
            break;

        case preg_match('/^\/article\/(.+)$/', $path, $matches):
            if (isset($matches[1]) && !empty($matches[1])) {
                $identifier = $matches[1];
                $controller = new ArticleController($db);
                $data = $controller->show($identifier);

                if ($data && $data['article']) {
                    echo $twig->render('article-detail.html.twig', $data);
                } else {
                    http_response_code(404);
                    echo $twig->render('404.html.twig', ['title' => 'Article Not Found']);
                }
            } else {
                http_response_code(404);
                echo $twig->render('404.html.twig', ['title' => 'Invalid Article URL']);
            }
            break;

        case preg_match('/^\/category\/(.+)$/', $path, $matches):
            if (isset($matches[1]) && !empty($matches[1])) {
                $slug = $matches[1];
                $controller = new CategoryController($db);
                $data = $controller->show($slug);

                if ($data) {
                    echo $twig->render('category.html.twig', $data);
                } else {
                    http_response_code(404);
                    echo $twig->render('404.html.twig', ['title' => 'Category Not Found']);
                }
            } else {
                http_response_code(404);
                echo $twig->render('404.html.twig', ['title' => 'Invalid Category URL']);
            }
            break;

        case $path === '/search':
            echo '<link rel="stylesheet" href="/css/search.css">';
            $query = $_GET['q'] ?? '';
            $articleRepository = new PDOArticleRepository($db);
            $categoryRepository = new PDOCategoryRepository($db);
            $searchUseCase = new SearchArticlesUseCase($articleRepository, $categoryRepository);
            $controller = new SearchController($searchUseCase);
            $data = $controller->search($query);
            echo $twig->render('search.html.twig', $data);
            break;

        case $path === '/login':
            echo '<link rel="stylesheet" href="/css/auth.css">';
            $controller = new AuthController($db);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log("[v0] Processing POST login request");
                $result = $controller->login();
                if (is_array($result)) {
                    error_log("[v0] Login failed, showing error");
                    $templateData = array_merge($result, ["session" => $_SESSION ?? []]);
                    echo $twig->render('auth/login.html.twig', $templateData);
                }
                // If login() redirects successfully, execution stops at exit
            } else {
                $data = $controller->showLogin();
                $templateData = array_merge($data, ["session" => $_SESSION ?? []]);
                echo $twig->render('auth/login.html.twig', $templateData);
            }
            break;

        case $path === '/profile':
            $controller = new ProfileController($db);
            $data = $controller->show();
            $templateData = array_merge($data, ["session" => $_SESSION ?? []]);
            echo $twig->render('profile.html.twig', $templateData);
            break;

        case $path === '/logout':
            $controller = new AuthController($db);
            $controller->logout();
            break;

        case $path === '/newsletter' || $path === '/subscribe':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Simple validation
                $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
                if ($email) {
                    // For now, just show success - no actual email sending
                    echo $twig->render('newsletter-success.html.twig', [
                        'title' => 'Subscription Successful',
                        'email' => $email
                    ]);
                } else {
                    echo $twig->render('newsletter.html.twig', [
                        'title' => 'Subscribe to Grip & Grin',
                        'error' => 'Please enter a valid email address.'
                    ]);
                }
            } else {
                echo $twig->render('newsletter.html.twig', ['title' => 'Subscribe to Grip & Grin']);
            }
            break;

        case $path === '/admin-dashboard':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->dashboard();
            $templateData = array_merge($data, ["session" => $_SESSION ?? []]);
            echo $twig->render('admin/dashboard.html.twig', $templateData);
            break;

        case $path === '/admin-articles':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->articlesOverview();
            echo $twig->render('admin/articles.html.twig', $data);
            break;

        case $path === '/admin-users':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->usersOverview();
            echo $twig->render('admin/users.html.twig', $data);
            break;

        case $path === '/admin-users/create':
            AdminMiddleware::requireStrictAdmin();
            $controller = new AdminController($db);
            $controller->createUser();
            break;

        case $path === '/admin-users/delete':
            AdminMiddleware::requireStrictAdmin();
            $controller = new AdminController($db);
            $controller->deleteUser();
            break;

        case $path === '/admin-users/reset-password':
            AdminMiddleware::requireStrictAdmin();
            $controller = new AdminController($db);
            $controller->resetUserPassword();
            break;

        case $path === '/admin-admins':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->adminsOverview();
            echo $twig->render('admin/admins.html.twig', $data);
            break;

        case $path === '/admin-gdpr-requests':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->gdprRequests();
            echo $twig->render('admin/gdpr-requests.html.twig', $data);
            break;

        case $path === '/admin-profile':
            AdminMiddleware::requireAdmin();
            $controller = new ProfileController($db);
            $data = $controller->adminProfile();
            echo $twig->render('admin/profile.html.twig', $data);
            break;

        case $path === '/admin-profile/change-password':
            AdminMiddleware::requireAdmin();
            $controller = new ProfileController($db);
            $controller->changePassword();
            break;

        case $path === '/admin-profile/update-email':
            AdminMiddleware::requireAdmin();
            $controller = new ProfileController($db);
            $controller->updateEmail();
            break;

        case $path === '/admin/articles':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->articlesOverview();
            echo $twig->render('admin/articles.html.twig', $data);
            break;

        case $path === '/admin/articles/delete':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $controller->deleteArticle();
            break;

        case $path === '/admin/articles/toggle-status':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $controller->toggleArticleStatus();
            break;

        case $path === '/admin/users/delete':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $controller->deleteUser();
            break;

        case $path === '/admin/articles/create':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->createArticle();
                exit;
            } else {
                $data = $controller->createArticle();
                $templateData = array_merge($data, ["session" => $_SESSION ?? []]);
                echo $twig->render('admin/article-form.html.twig', $templateData);
            }
            break;

        case $path === '/admin/articles/edit':
            AdminMiddleware::requireAdmin();
            $controller = new AdminController($db);
            $data = $controller->editArticle();
            echo $twig->render('admin/article-form.html.twig', $data);
            break;

        case $path === '/privacy-policy':
            echo $twig->render('gdpr/privacy-policy-simple.html.twig', ['title' => 'Privacy Policy']);
            break;

        default:
            http_response_code(404);
            echo $twig->render('404.html.twig', ['title' => 'Page Not Found']);
            break;
    }

} catch (Exception $e) {
    error_log("Website Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    try {
        echo $twig->render('500.html.twig', [
            'title' => 'Server Error',
            'error' => 'An unexpected error occurred.'
        ]);
    } catch (Exception $templateError) {
        error_log("Template error: " . $templateError->getMessage());
        echo "Something went wrong. Please try again.";
    }
}
?>
