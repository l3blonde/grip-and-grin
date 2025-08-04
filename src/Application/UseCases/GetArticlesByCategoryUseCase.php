<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\Interfaces\CategoryRepositoryInterface;

class GetArticlesByCategoryUseCase
{
    private const ARTICLES_PER_PAGE = 5;

    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(string $categorySlug, int $page = 1): array
    {
        $category = $this->categoryRepository->findBySlug($categorySlug);

        if (!$category) {
            return [
                'articles' => [],
                'category' => null,
                'currentPage' => 1,
                'totalPages' => 0,
                'hasNextPage' => false,
                'hasPreviousPage' => false,
                'nextPage' => null,
                'previousPage' => null,
            ];
        }

        $offset = ($page - 1) * self::ARTICLES_PER_PAGE;
        $articles = $this->articleRepository->findByCategoryPaginated($category->getId(), self::ARTICLES_PER_PAGE, $offset);
        $totalArticles = $this->articleRepository->countByCategory($category->getId());
        $totalPages = (int) ceil($totalArticles / self::ARTICLES_PER_PAGE);

        return [
            'articles' => $articles,
            'category' => $category,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
            'nextPage' => $page < $totalPages ? $page + 1 : null,
            'previousPage' => $page > 1 ? $page - 1 : null,
        ];
    }
}
