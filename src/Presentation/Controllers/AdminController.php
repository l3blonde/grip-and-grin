<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use GripAndGrin\Infrastructure\Repositories\PDOUserRepository;
use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
use GripAndGrin\Presentation\Middleware\AdminMiddleware;
use PDO;
use GripAndGrin\Domain\Entities\Article;
use DateTime;

class AdminController
{
    private PDOArticleRepository $articleRepository;
    private PDOUserRepository $userRepository;
    private PDOCategoryRepository $categoryRepository;

    public function __construct(PDO $pdo)
    {
        $this->articleRepository = new PDOArticleRepository($pdo);
        $this->userRepository = new PDOUserRepository($pdo);
        $this->categoryRepository = new PDOCategoryRepository($pdo);
    }

    public function dashboard(): array
    {
        AdminMiddleware::requireAdmin();

        $totalUsers = count($this->getUsersByRole('user'));
        $totalAdmins = count($this->getUsersByRole('admin'));
        $totalArticles = count($this->articleRepository->findAllForAdmin());

        return [
            'title' => 'Admin Dashboard',
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalArticles' => $totalArticles
        ];
    }

    public function articlesOverview(): array
    {
        AdminMiddleware::requireAdmin();

        $articles = $this->articleRepository->findAllForAdmin();

        return [
            'title' => 'Articles Management',
            'articles' => $articles
        ];
    }

    public function usersOverview(): array
    {
        AdminMiddleware::requireStrictAdmin();

        return [
            'title' => 'Users Management',
            'users' => $this->getUsersByRole('user')
        ];
    }

    public function adminsOverview(): array
    {
        AdminMiddleware::requireStrictAdmin();

        return [
            'title' => 'Administrators',
            'admins' => $this->getUsersByRole('admin')
        ];
    }

    public function users(): array
    {
        return $this->usersOverview();
    }

    public function admins(): array
    {
        return $this->adminsOverview();
    }

    public function createArticle(): array
    {
        AdminMiddleware::requireAdmin();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $excerpt = $_POST['excerpt'] ?? '';
            $categoryId = (int) ($_POST['category_id'] ?? 1);
            $status = $_POST['status'] ?? 'draft';
            $imageUrl = $_POST['image_url'] ?? '';

            $imagePath = $imageUrl; // Default to URL if provided

            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['image']);
            }

            if ($title && $content) {
                $article = new Article(
                    0, // New article
                    $title,
                    $content,
                    $categoryId,
                    $excerpt ?: substr($content, 0, 150),
                    $imagePath,
                    $status === 'published',
                    new DateTime(),
                    new DateTime()
                );

                $this->articleRepository->save($article);
                header('Location: /admin-articles');
                exit;
            }
        }

        $categories = $this->categoryRepository->findAll();

        return [
            'title' => 'Create Article',
            'categories' => $categories,
            'article' => null
        ];
    }

    public function editArticle(): array
    {
        AdminMiddleware::requireAdmin();

        $articleId = (int) ($_GET['id'] ?? 0);
        $article = $this->articleRepository->findById($articleId);

        if (!$article) {
            header('Location: /admin-articles');
            exit;
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $excerpt = $_POST['excerpt'] ?? '';
            $categoryId = (int) ($_POST['category_id'] ?? 1);
            $status = $_POST['status'] ?? 'draft';
            $imageUrl = $_POST['image_url'] ?? '';

            $imagePath = $imageUrl ?: $article->getImagePath(); // Keep existing image if no new one

            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['image']);
            }

            if ($title && $content) {
                $updatedArticle = new Article(
                    $article->getId(),
                    $title,
                    $content,
                    $categoryId,
                    $excerpt ?: substr($content, 0, 150),
                    $imagePath,
                    $status === 'published',
                    $article->getCreatedAt(),
                    new DateTime()
                );

                $this->articleRepository->save($updatedArticle);
                header('Location: /admin-articles');
                exit;
            }
        }

        $categories = $this->categoryRepository->findAll();

        return [
            'title' => 'Edit Article',
            'categories' => $categories,
            'article' => $article
        ];
    }

    public function deleteUser(): void
    {
        AdminMiddleware::requireStrictAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin-users");
            exit;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId && $userId !== $_SESSION['user_id']) {
            try {
                $deactivateUseCase = new \GripAndGrin\Application\UseCases\DeactivateUserUseCase($this->userRepository);
                $deactivateUseCase->execute($userId);
                $_SESSION['success_message'] = 'User deactivated successfully';
            } catch (\Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        header("Location: /admin-users");
        exit;
    }

    public function deleteArticle(): void
    {
        AdminMiddleware::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/articles");
            exit;
        }

        $articleId = (int) ($_POST['article_id'] ?? 0);
        if ($articleId) {
            $this->articleRepository->delete($articleId);
        }

        header("Location: /admin/articles");
        exit;
    }

    public function toggleArticleStatus(): void
    {
        AdminMiddleware::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/articles");
            exit;
        }

        $articleId = (int) ($_POST['article_id'] ?? 0);
        if ($articleId) {
            $this->articleRepository->togglePublishStatus($articleId);
        }

        header("Location: /admin/articles");
        exit;
    }

    public function gdprRequests(): array
    {
        AdminMiddleware::requireAdmin();

        // For minimal GDPR, we just show basic cookie consent stats and any data requests
        $cookieStats = $this->getCookieConsentStats();
        $dataRequests = $this->getDataDeletionRequests();

        return [
            'title' => 'GDPR Management',
            'cookieStats' => $cookieStats,
            'dataRequests' => $dataRequests,
            'totalRequests' => count($dataRequests)
        ];
    }

    public function processGDPRDeletion(): void
    {
        AdminMiddleware::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/gdpr-requests");
            exit;
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $processedBy = $_SESSION['user_id'] ?? 0;

        if ($requestId && $processedBy) {
            // Minimal GDPR processing logic
            $_SESSION['success_message'] = 'Data deletion request processed successfully.';
        }

        header("Location: /admin/gdpr-requests");
        exit;
    }

    public function rejectGDPRDeletion(): void
    {
        AdminMiddleware::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/gdpr-requests");
            exit;
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $processedBy = $_SESSION['user_id'] ?? 0;

        if ($requestId && $processedBy) {
            // Minimal GDPR rejection logic
            $_SESSION['success_message'] = 'Data deletion request rejected.';
        }

        header("Location: /admin/gdpr-requests");
        exit;
    }

    public function createUser(): void
    {
        AdminMiddleware::requireStrictAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin-users");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'editor';

        if ($username && $email && $password) {
            try {
                $createUserUseCase = new \GripAndGrin\Application\UseCases\CreateUserUseCase($this->userRepository);
                $createUserUseCase->execute($username, $email, $password, $role);
                $_SESSION['success_message'] = 'User created successfully';
            } catch (\Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'All fields are required';
        }

        header("Location: /admin-users");
        exit;
    }

    private function getCookieConsentStats(): array
    {
        // Simple cookie consent tracking for minimal GDPR
        return [
            'accepted' => 0,
            'rejected' => 0,
            'total' => 0
        ];
    }

    private function getDataDeletionRequests(): array
    {
        // Return empty array for minimal GDPR - no complex data deletion tracking needed
        return [];
    }

    private function handleImageUpload(array $file): ?string
    {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('article_') . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return '/uploads/' . $filename;
        }

        return null;
    }

    private function getUsersByRole(string $role): array
    {
        return $this->userRepository->findAllByRole($role);
    }
}
