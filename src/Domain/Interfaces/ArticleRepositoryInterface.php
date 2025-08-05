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

    public function findById(int $id): ?Article;

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

    /**
     * @return Article[]
     */
    public function findByCategoryPaginated(int $categoryId, int $limit, int $offset): array;

    public function countByCategory(int $categoryId): int;

    /**
     * @return Article[]
     */
    public function findAll(): array;

    /**
     * @return Article[]
     */
    public function findAllPaginated(int $limit, int $offset): array;

    public function countAll(): int;

    public function save(Article $article): Article;

    public function delete(int $id): bool;
}
