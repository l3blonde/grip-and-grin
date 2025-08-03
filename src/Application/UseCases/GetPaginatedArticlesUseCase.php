<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;

class GetPaginatedArticlesUseCase
{
    private const ARTICLES_PER_PAGE = 5;

    public function __construct(private readonly ArticleRepositoryInterface $articleRepository)
    {
    }

    public function execute(int $page = 1): array
    {
        $offset = ($page - 1) * self::ARTICLES_PER_PAGE;
        $articles = $this->articleRepository->findAllPublishedPaginated(self::ARTICLES_PER_PAGE, $offset);
        $totalArticles = $this->articleRepository->countAllPublished();
        $totalPages = (int) ceil($totalArticles / self::ARTICLES_PER_PAGE);

        return [
            'articles' => $articles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
            'nextPage' => $page < $totalPages ? $page + 1 : null,
            'previousPage' => $page > 1 ? $page - 1 : null,
        ];
    }
}
