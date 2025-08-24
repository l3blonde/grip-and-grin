<?php

declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\Interfaces\CategoryRepositoryInterface;

class GetArticlesByCategoryUseCase
{
    private ArticleRepositoryInterface $articleRepository;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->articleRepository = $articleRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function execute(string $categorySlug, int $page = 1, int $perPage = 12): ?array
    {
        $category = $this->categoryRepository->findBySlug($categorySlug);
        
        if (!$category) {
            return null;
        }

        $offset = ($page - 1) * $perPage;
        $articles = $this->articleRepository->findByCategory($category->getId(), $perPage, $offset);
        $totalArticles = $this->articleRepository->countByCategory($category->getId());
        $totalPages = (int) ceil($totalArticles / $perPage);

        return [
            'category' => $category,
            'articles' => $articles,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalArticles' => $totalArticles,
                'perPage' => $perPage,
                'hasNextPage' => $page < $totalPages,
                'hasPreviousPage' => $page > 1,
                'nextPage' => $page < $totalPages ? $page + 1 : null,
                'previousPage' => $page > 1 ? $page - 1 : null
            ]
        ];
    }
}
