<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;

class SearchArticlesUseCase
{
    private const ARTICLES_PER_PAGE = 5;

    public function __construct(private readonly ArticleRepositoryInterface $articleRepository)
    {
    }

    public function execute(string $query, int $page = 1): array
    {
        if (empty(trim($query))) {
            return [
                'articles' => [],
                'currentPage' => 1,
                'totalPages' => 0,
                'hasNextPage' => false,
                'hasPreviousPage' => false,
                'nextPage' => null,
                'previousPage' => null,
                'query' => $query,
            ];
        }

        $offset = ($page - 1) * self::ARTICLES_PER_PAGE;
        $articles = $this->articleRepository->searchArticles($query, self::ARTICLES_PER_PAGE, $offset);
        $totalArticles = $this->articleRepository->countSearchResults($query);
        $totalPages = (int) ceil($totalArticles / self::ARTICLES_PER_PAGE);

        return [
            'articles' => $articles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
            'nextPage' => $page < $totalPages ? $page + 1 : null,
            'previousPage' => $page > 1 ? $page - 1 : null,
            'query' => $query,
        ];
    }
}
