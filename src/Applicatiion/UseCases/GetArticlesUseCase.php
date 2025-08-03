<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;

class GetArticlesUseCase
{
    public function __construct(private readonly ArticleRepositoryInterface $articleRepository)
    {
    }

    public function execute(): array
    {
        return $this->articleRepository->findAllPublished();
    }
}
