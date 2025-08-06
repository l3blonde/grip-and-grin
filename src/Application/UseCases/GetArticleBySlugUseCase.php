<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;

class GetArticleBySlugUseCase
{
    public function __construct(private readonly ArticleRepositoryInterface $articleRepository)
    {
    }

    public function execute(string $slug): array
    {
        $article = $this->articleRepository->findBySlug($slug);

        if (!$article) {
            return [
                'article' => null,
                'nextArticle' => null,
                'previousArticle' => null
            ];
        }

        $nextArticle = $this->articleRepository->findNextArticle($article);
        $previousArticle = $this->articleRepository->findPreviousArticle($article);

        return [
            'article' => $article,
            'nextArticle' => $nextArticle,
            'previousArticle' => $previousArticle
        ];
    }
}
