<?php

declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\Interfaces\CategoryRepositoryInterface;

class SearchArticlesUseCase
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

    public function execute(string $query, int $page = 1, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;
        $articles = $this->articleRepository->search($query, $perPage, $offset);
        
        // Enrich articles with category data
        $enrichedArticles = [];
        foreach ($articles as $article) {
            $category = null;
            if ($article->getCategoryId()) {
                $category = $this->categoryRepository->findById($article->getCategoryId());
            }
            
            $enrichedArticles[] = [
                'article' => $article,
                'category' => $category
            ];
        }

        // <CHANGE> Fixed count method - get total results by searching without limit
        $totalResults = $this->getTotalSearchResults($query);
        $totalPages = (int) ceil($totalResults / $perPage);
        $hasNextPage = $page < $totalPages;
        $hasPreviousPage = $page > 1;

        return [
            'articles' => $articles,
            'totalCount' => $totalResults,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalResults' => $totalResults,
                'hasNextPage' => $page < $totalPages,
                'hasPreviousPage' => $page > 1
            ]
        ];
    }
    
    // <CHANGE> Added method to get total search results using existing search method
    private function getTotalSearchResults(string $query): int
    {
        try {
            // Get all results without pagination to count them
            $allResults = $this->articleRepository->search($query, 1000, 0); // Large limit to get all
            return count($allResults);
        } catch (\Exception $e) {
            // Fallback: return 0 if search fails
            error_log('Search count error: ' . $e->getMessage());
            return 0;
        }
    }
}
