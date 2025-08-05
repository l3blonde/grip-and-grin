<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\CreateArticleUseCase;
use GripAndGrin\Application\UseCases\GetAllUsersUseCase;
use GripAndGrin\Application\UseCases\GetCategoriesUseCase;
use GripAndGrin\Application\UseCases\GetPaginatedArticlesUseCase;
use GripAndGrin\Application\UseCases\UpdateArticleUseCase;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Infrastructure\Middleware\AdminMiddleware;
use GripAndGrin\Infrastructure\Services\SessionService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AdminController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly AdminMiddleware $adminMiddleware,
        private readonly SessionService $sessionService,
        private readonly GetPaginatedArticlesUseCase $getPaginatedArticlesUseCase,
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly GetCategoriesUseCase $getCategoriesUseCase,
        private readonly CreateArticleUseCase $createArticleUseCase,
        private readonly UpdateArticleUseCase $updateArticleUseCase,
        private readonly GetAllUsersUseCase $getAllUsersUseCase
    ) {}

    public function dashboard(): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        $content = $this->twig->render('admin/dashboard.html.twig', [
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }

    public function articles(Request $request): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        $page = max(1, (int) $request->query->get('page', 1));

        // Get all articles (not just published) for admin
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $articles = $this->articleRepository->findAllPaginated($limit, $offset);
        $totalArticles = $this->articleRepository->countAll();
        $totalPages = (int) ceil($totalArticles / $limit);

        $content = $this->twig->render('admin/articles.html.twig', [
            'articles' => $articles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
            'nextPage' => $page < $totalPages ? $page + 1 : null,
            'previousPage' => $page > 1 ? $page - 1 : null,
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }

    public function createArticleForm(): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        $categories = $this->getCategoriesUseCase->execute();

        $content = $this->twig->render('admin/create-article.html.twig', [
            'categories' => $categories,
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }

    public function createArticle(Request $request): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        if ($request->getMethod() !== 'POST') {
            return new RedirectResponse('/admin/articles/create');
        }

        $title = $request->request->get('title', '');
        $content = $request->request->get('content', '');
        $excerpt = $request->request->get('excerpt', '');
        $categoryId = (int) $request->request->get('category_id', 0);
        $status = $request->request->get('status', 'draft');
        $imageAltText = $request->request->get('image_alt_text', '');
        $csrfToken = $request->request->get('csrf_token', '');

        try {
            // Validate CSRF token
            if (!$this->sessionService->validateCsrfToken($csrfToken)) {
                throw new InvalidArgumentException('Invalid security token');
            }

            $authorId = $this->sessionService->getCurrentUserId();
            $uploadedImage = $_FILES['featured_image'] ?? null;

            $article = $this->createArticleUseCase->execute(
                $title,
                $content,
                $excerpt,
                $authorId,
                $categoryId,
                $status,
                $uploadedImage,
                $imageAltText
            );

            return new RedirectResponse('/admin/articles?created=1');
        } catch (InvalidArgumentException $e) {
            $categories = $this->getCategoriesUseCase->execute();

            $content = $this->twig->render('admin/create-article.html.twig', [
                'error' => $e->getMessage(),
                'categories' => $categories,
                'title' => $title,
                'content' => $content,
                'excerpt' => $excerpt,
                'category_id' => $categoryId,
                'status' => $status,
                'image_alt_text' => $imageAltText,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content, 400);
        }
    }

    public function editArticleForm(int $id): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        $article = $this->articleRepository->findById($id);
        if (!$article) {
            return new Response('Article not found', 404);
        }

        $categories = $this->getCategoriesUseCase->execute();

        $content = $this->twig->render('admin/edit-article.html.twig', [
            'article' => $article,
            'categories' => $categories,
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }

    public function updateArticle(int $id, Request $request): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        if ($request->getMethod() !== 'POST') {
            return new RedirectResponse("/admin/articles/{$id}/edit");
        }

        $title = $request->request->get('title', '');
        $content = $request->request->get('content', '');
        $excerpt = $request->request->get('excerpt', '');
        $categoryId = (int) $request->request->get('category_id', 0);
        $status = $request->request->get('status', 'draft');
        $imageAltText = $request->request->get('image_alt_text', '');
        $removeImage = $request->request->get('remove_image', false);
        $csrfToken = $request->request->get('csrf_token', '');

        try {
            // Validate CSRF token
            if (!$this->sessionService->validateCsrfToken($csrfToken)) {
                throw new InvalidArgumentException('Invalid security token');
            }

            $uploadedImage = $_FILES['featured_image'] ?? null;

            $article = $this->updateArticleUseCase->execute(
                $id,
                $title,
                $content,
                $excerpt,
                $categoryId,
                $status,
                $uploadedImage,
                $imageAltText,
                (bool) $removeImage
            );

            return new RedirectResponse('/admin/articles?updated=1');
        } catch (InvalidArgumentException $e) {
            $article = $this->articleRepository->findById($id);
            $categories = $this->getCategoriesUseCase->execute();

            $content = $this->twig->render('admin/edit-article.html.twig', [
                'error' => $e->getMessage(),
                'article' => $article,
                'categories' => $categories,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content, 400);
        }
    }

    public function deleteArticle(int $id, Request $request): Response
    {
        $accessCheck = $this->adminMiddleware->handle();
        if ($accessCheck) return $accessCheck;

        if ($request->getMethod() !== 'POST') {
            return new RedirectResponse('/admin/articles');
        }

        $csrfToken = $request->request->get('csrf_token', '');

        try {
            // Validate CSRF token
            if (!$this->sessionService->validateCsrfToken($csrfToken)) {
                throw new InvalidArgumentException('Invalid security token');
            }

            $this->articleRepository->delete($id);
            return new RedirectResponse('/admin/articles?deleted=1');
        } catch (InvalidArgumentException $e) {
            return new RedirectResponse('/admin/articles?error=' . urlencode($e->getMessage()));
        }
    }

    public function users(): Response
    {
        $accessCheck = $this->adminMiddleware->requireAdmin();
        if ($accessCheck) return $accessCheck;

        $users = $this->getAllUsersUseCase->execute();

        $content = $this->twig->render('admin/users.html.twig', [
            'users' => $users,
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }
}
