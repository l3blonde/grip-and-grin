<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;

class GetArticleBySlugUseCase
{
    private ArticleRepositoryInterface $articleRepository;

    public function __construct(ArticleRepositoryInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
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
            'article' => [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'content' => $article->getContent(),
                'excerpt' => $article->getDisplayExcerpt(),
                'publishedAt' => $article->getPublishedAt(),
                'featuredImage' => $article->getFeaturedImage(),
                'imageThumbnailPath' => $article->getImageThumbnailPath(),
                'imageFullPath' => $article->getImageFullPath(),
                'imageAltText' => $article->getImageAltText()
            ],
            'nextArticle' => $nextArticle ? [
                'title' => $nextArticle->getTitle(),
                'slug' => $nextArticle->getSlug()
            ] : null,
            'previousArticle' => $previousArticle ? [
                'title' => $previousArticle->getTitle(),
                'slug' => $previousArticle->getSlug()
            ] : null
        ];
    }
}
