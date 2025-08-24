<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;

class GetPaginatedArticlesUseCase
{
    private const ARTICLES_PER_PAGE = 12;

    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository
    ) {}

    public function execute(int $page = 1): array
    {
        $limit = self::ARTICLES_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $articles = $this->articleRepository->findPublished($limit, $offset);
        $totalArticles = $this->articleRepository->countPublished();
        $totalPages = (int) ceil($totalArticles / $limit);

        return [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_articles' => $totalArticles,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null,
            ]
        ];
    }
}
