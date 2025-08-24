<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Interfaces;

use GripAndGrin\Domain\Entities\Article;

interface ArticleRepositoryInterface
{
    public function findById(int $id): ?Article;
    public function findBySlug(string $slug): ?Article;
    public function findPublished(int $limit = 10, int $offset = 0): array;
    public function findByCategory(int $categoryId, int $limit = 10, int $offset = 0): array;
    public function findByCategorySlug(string $categorySlug): array;
    public function countPublished(): int;
    public function countByCategory(int $categoryId): int;
    public function search(string $query, int $limit = 10, int $offset = 0): array;
    public function findNextArticle(Article $article): ?Article;
    public function findPreviousArticle(Article $article): ?Article;
    public function findAllPublished(): array;
    public function findAllPublishedPaginated(int $limit, int $offset): array;
    public function countAllPublished(): int;
    public function searchPublished(string $query, int $limit = 10, int $offset = 0): array;
    public function getLatest(int $limit = 5): array;
    public function getFeatured(): array;
    public function save(Article $article): Article;
    public function delete(int $id): bool;
}
