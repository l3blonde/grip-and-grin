<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Interfaces;

use GripAndGrin\Domain\Entities\Article;

interface ArticleRepositoryInterface
{
    public function findAllPublished(): array;
    public function findBySlug(string $slug): ?Article;
    public function findById(int $id): ?Article;
    public function findAll(): array;
    public function findAllPaginated(int $limit, int $offset): array;
    public function countAll(): int;
    public function findAllPublishedPaginated(int $limit, int $offset): array;
    public function countAllPublished(): int;
    public function searchArticles(string $query, int $limit, int $offset): array;
    public function countSearchResults(string $query): int;
    public function findByCategoryPaginated(int $categoryId, int $limit, int $offset): array;
    public function countByCategory(int $categoryId): int;
    public function findNextArticle(Article $currentArticle): ?Article;
    public function findPreviousArticle(Article $currentArticle): ?Article;
    public function save(Article $article): Article;
    public function delete(int $id): bool;
}
