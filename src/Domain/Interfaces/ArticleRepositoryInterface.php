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

    /**
     * @return Article[]
     */
    public function findAllPublishedPaginated(int $limit, int $offset): array;

    public function countAllPublished(): int;

    /**
     * @return Article[]
     */
    public function searchArticles(string $query, int $limit, int $offset): array;

    public function countSearchResults(string $query): int;
}
