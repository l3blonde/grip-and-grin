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

    public function execute(string $slug): ?Article
    {
        return $this->articleRepository->findBySlug($slug);
    }
}
