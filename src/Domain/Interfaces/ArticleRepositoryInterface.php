<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Interfaces;

use GripAndGrin\Domain\Entities\Article;

interface ArticleRepositoryInterface
{
    /**
     * @return Article[]
     */
    public function findAllPublished(): array;

    public function findBySlug(string $slug): ?Article;
}
