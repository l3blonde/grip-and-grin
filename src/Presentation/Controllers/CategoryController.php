<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use PDO;

class CategoryController
{
    private PDOCategoryRepository $categoryRepository;
    private PDOArticleRepository $articleRepository;

    public function __construct(PDO $pdo)
    {
        $this->categoryRepository = new PDOCategoryRepository($pdo);
        $this->articleRepository = new PDOArticleRepository($pdo);
    }

    public function show(string $slug): ?array
    {
        $category = $this->categoryRepository->findBySlug($slug);
        
        if (!$category) {
            return null;
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = 5; // Same as home page - 5 articles per page
        $offset = ($page - 1) * $limit;

        $articles = $this->articleRepository->findByCategory($category->getId(), $limit, $offset);
        $totalArticles = $this->articleRepository->countByCategory($category->getId());
        $totalPages = (int)ceil($totalArticles / $limit);

        $articlesData = [];
        foreach ($articles as $article) {
            $articlesData[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getDisplayExcerpt(),
                'publishedAt' => $article->getPublishedAt(),
                'featuredImage' => $article->getFeaturedImage(),
                'imageThumbnailPath' => $article->getImageThumbnailPath(),
                'imageAltText' => $article->getImageAltText()
            ];
        }

        return [
            'category' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'description' => $category->getDescription()
            ],
            'articles' => $articlesData,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasPreviousPage' => $page > 1,
            'hasNextPage' => $page < $totalPages,
            'previousPage' => $page - 1,
            'nextPage' => $page + 1
        ];
    }
}
