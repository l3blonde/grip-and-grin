<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use GripAndGrin\Infrastructure\Repositories\PDOCategoryRepository;
use GripAndGrin\Application\UseCases\SearchArticlesUseCase;
use PDO;

class SearchController
{
    private SearchArticlesUseCase $searchArticlesUseCase;

    public function __construct(SearchArticlesUseCase $searchArticlesUseCase)
    {
        $this->searchArticlesUseCase = $searchArticlesUseCase;
    }

    public function search(string $query = ''): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 5;
        
        if (empty(trim($query))) {
            // Show all articles when no search query
            $articleRepository = $this->searchArticlesUseCase->getArticleRepository();
            $articles = $articleRepository->findAllPublished($perPage, ($page - 1) * $perPage);
            $totalResults = $articleRepository->countAllPublished();
            $totalPages = (int) ceil($totalResults / $perPage);
            
            return [
                'title' => 'All Articles',
                'articles' => $this->transformArticles($articles),
                'query' => '',
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalResults' => $totalResults,
                'perPage' => $perPage,
                'hasNextPage' => $page < $totalPages,
                'hasPreviousPage' => $page > 1,
                'nextPage' => $page < $totalPages ? $page + 1 : null,
                'previousPage' => $page > 1 ? $page - 1 : null
            ];
        }

        $result = $this->searchArticlesUseCase->execute($query, $page, $perPage);
        
        return [
            'title' => 'Search Results',
            'articles' => $result['articles'] ?? [],
            'query' => $query,
            'currentPage' => $result['pagination']['currentPage'] ?? $page,
            'totalPages' => $result['pagination']['totalPages'] ?? 1,
            'totalResults' => $result['pagination']['totalResults'] ?? 0,
            'perPage' => $perPage,
            'hasNextPage' => $result['pagination']['hasNextPage'] ?? false,
            'hasPreviousPage' => $result['pagination']['hasPreviousPage'] ?? false,
            'nextPage' => $result['pagination']['nextPage'] ?? null,
            'previousPage' => $result['pagination']['previousPage'] ?? null
        ];
    }

    private function transformArticles(array $articles): array
    {
        $transformed = [];
        foreach ($articles as $article) {
            $transformed[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getDisplayExcerpt(),
                'publishedAt' => $article->getPublishedAt(),
                'createdAt' => $article->getCreatedAt(),
                'featuredImage' => $article->getFeaturedImage(),
                'imageThumbnailPath' => $article->getImageThumbnailPath(),
                'imageAltText' => $article->getImageAltText()
            ];
        }
        return $transformed;
    }
}
