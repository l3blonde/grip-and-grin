<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
use PDO;

class HomeController
{
    private PDOArticleRepository $articleRepository;
    private PDOCategoryRepository $categoryRepository;

    public function __construct(PDO $pdo)
    {
        $this->articleRepository = new PDOArticleRepository($pdo);
        $this->categoryRepository = new PDOCategoryRepository($pdo);
    }

    public function index(): array
    {
        try {
            // <CHANGE> Added pagination functionality back
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = 5;
            $offset = ($page - 1) * $perPage;
            
            // Get paginated articles
            $articles = $this->articleRepository->findAllPublished($perPage, $offset);
            $totalArticles = $this->articleRepository->countAllPublished();
            $totalPages = (int) ceil($totalArticles / $perPage);
            
            // <CHANGE> Fixed array conversion to include all needed properties
            $articleData = [];
            foreach ($articles as $article) {
                $articleData[] = [
                    'id' => $article->getId(),
                    'title' => $article->getTitle(),
                    'content' => $article->getContent(),
                    'excerpt' => $article->getExcerpt() ?: substr(strip_tags($article->getContent()), 0, 150) . '...',
                    'categoryName' => $this->getCategoryName($article->getCategoryId()),
                    'imagePath' => $article->getFeaturedImage(),
                    'isPublished' => $article->getStatus() === 'published',
                    'createdAt' => $article->getCreatedAt(),
                    'updatedAt' => $article->getUpdatedAt(),
                    'slug' => $article->getSlug()
                ];
            }

            // <CHANGE> Added pagination data back
            return [
                'articles' => $articleData,
                'title' => 'Grip & Grin - Classic Car Blog',
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalArticles' => $totalArticles,
                'hasPreviousPage' => $page > 1,
                'hasNextPage' => $page < $totalPages,
                'previousPage' => max(1, $page - 1),
                'nextPage' => min($totalPages, $page + 1)
            ];
        } catch (\Exception $e) {
            error_log('HomeController error: ' . $e->getMessage());
            return [
                'articles' => [],
                'title' => 'Grip & Grin - Classic Car Blog',
                'currentPage' => 1,
                'totalPages' => 0,
                'totalArticles' => 0,
                'hasPreviousPage' => false,
                'hasNextPage' => false,
                'error' => 'Unable to load articles'
            ];
        }
    }
    
    // <CHANGE> Added helper method to get category name
    private function getCategoryName(int $categoryId): ?string
    {
        try {
            $category = $this->categoryRepository->findById($categoryId);
            return $category ? $category->getName() : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
