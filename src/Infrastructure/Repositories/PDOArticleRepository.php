<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use PDO;
use DateTime;

class PDOArticleRepository implements ArticleRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function findById(int $id): ?Article
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findBySlug(string $slug): ?Article
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findPublished(int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findByCategory(int $categoryId, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE category_id = ? AND status = 'published' ORDER BY published_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$categoryId, $limit, $offset]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countPublished(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        return (int) $stmt->fetchColumn();
    }

    public function countByCategory(int $categoryId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published'");
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn();
    }

    public function search(string $query, int $limit = 10, int $offset = 0): array
    {
        $searchTerm = "%{$query}%";
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE (title LIKE ? OR content LIKE ?) AND status = 'published' ORDER BY published_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function save(Article $article): Article
    {
        if ($article->getId() === 0) {
            // Create new article
            $stmt = $this->pdo->prepare("
                INSERT INTO articles (title, slug, content, excerpt, author_id, category_id, status, published_at, created_at, updated_at, featured_image, image_alt_text) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)
            ");

            $stmt->execute([
                $article->getTitle(),
                $article->getSlug(),
                $article->getContent(),
                $article->getExcerpt(),
                $article->getAuthorId(),
                $article->getCategoryId(),
                $article->getStatus(),
                $article->getPublishedAt() ? $article->getPublishedAt()->format('Y-m-d H:i:s') : null,
                $article->getFeaturedImage(),
                $article->getImageAltText()
            ]);

            $articleId = (int) $this->pdo->lastInsertId();
            return $this->findById($articleId);
        } else {
            // Update existing article
            $stmt = $this->pdo->prepare("
                UPDATE articles SET 
                    title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, 
                    status = ?, published_at = ?, updated_at = NOW(), 
                    featured_image = ?, image_alt_text = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $article->getTitle(),
                $article->getSlug(),
                $article->getContent(),
                $article->getExcerpt(),
                $article->getCategoryId(),
                $article->getStatus(),
                $article->getPublishedAt() ? $article->getPublishedAt()->format('Y-m-d H:i:s') : null,
                $article->getFeaturedImage(),
                $article->getImageAltText(),
                $article->getId()
            ]);

            return $this->findById($article->getId());
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findAllPublished(?int $limit = null): array
    {
        if ($limit === null) {
            $stmt = $this->pdo->query("SELECT * FROM articles WHERE status = 'published' ORDER BY published_at DESC");
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT ?");
            $stmt->execute([$limit]);
        }
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findAllPublishedPaginated(int $limit, int $offset): array
    {
        return $this->findPublished($limit, $offset);
    }

    public function countAllPublished(): int
    {
        return $this->countPublished();
    }

    public function findByCategorySlug(string $categorySlug): array
    {
        $stmt = $this->pdo->prepare("SELECT a.* FROM articles a INNER JOIN categories c ON a.category_id = c.id WHERE c.slug = ? AND a.status = 'published' ORDER BY a.published_at DESC");
        $stmt->execute([$categorySlug]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findNextArticle(Article $currentArticle): ?Article
    {
        if (!$currentArticle->getPublishedAt()) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE published_at > ? AND status = 'published' ORDER BY published_at ASC LIMIT 1");
        $stmt->execute([$currentArticle->getPublishedAt()->format('Y-m-d H:i:s')]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findPreviousArticle(Article $currentArticle): ?Article
    {
        if (!$currentArticle->getPublishedAt()) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE published_at < ? AND status = 'published' ORDER BY published_at DESC LIMIT 1");
        $stmt->execute([$currentArticle->getPublishedAt()->format('Y-m-d H:i:s')]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findAllPaginated(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articles ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM articles");
        return (int) $stmt->fetchColumn();
    }

    public function searchArticles(string $query, int $limit, int $offset): array
    {
        return $this->search($query, $limit, $offset);
    }

    public function countSearchResults(string $query): int
    {
        $searchTerm = "%{$query}%";
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM articles WHERE (title LIKE ? OR content LIKE ?) AND status = 'published'");
        $stmt->execute([$searchTerm, $searchTerm]);
        return (int) $stmt->fetchColumn();
    }

    public function findByCategoryPaginated(int $categoryId, int $limit, int $offset): array
    {
        return $this->findByCategory($categoryId, $limit, $offset);
    }

    public function findByCategorySlugPaginated(string $categorySlug, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT a.* FROM articles a INNER JOIN categories c ON a.category_id = c.id WHERE c.slug = ? AND a.status = 'published' ORDER BY a.published_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$categorySlug, $limit, $offset]);
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countByCategorySlug(string $categorySlug): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM articles a INNER JOIN categories c ON a.category_id = c.id WHERE c.slug = ? AND a.status = 'published'");
        $stmt->execute([$categorySlug]);
        return (int) $stmt->fetchColumn();
    }

    public function searchPublished(string $query, int $limit = 10, int $offset = 0): array
    {
        return $this->search($query, $limit, $offset);
    }

    public function getLatest(int $limit = 10): array
    {
        return $this->findPublished($limit, 0);
    }

    public function getFeatured(int $limit = 10): array
    {
        // Return latest published articles as featured (can be enhanced later with a featured flag)
        return $this->findPublished($limit, 0);
    }

    public function findAllForAdmin(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function togglePublishStatus(int $articleId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE articles SET status = CASE WHEN status = 'published' THEN 'draft' ELSE 'published' END WHERE id = ?");
        return $stmt->execute([$articleId]);
    }

    private function mapToEntity(array $data): Article
    {
        return new Article(
            (int) $data['id'],
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['excerpt'],
            (int) ($data['author_id'] ?? 1),
            (int) ($data['category_id'] ?? 1),
            $data['status'], // Passing string status directly as expected by Article constructor
            $data['published_at'] ? new DateTime($data['published_at']) : null,
            $data['created_at'] ? new DateTime($data['created_at']) : new DateTime(),
            $data['updated_at'] ? new DateTime($data['updated_at']) : new DateTime(),
            $data['featured_image'] ?? $data['image_original_path'] ?? null,
            $data['image_thumbnail_path'] ?? null,
            $data['image_medium_path'] ?? null,
            $data['image_full_path'] ?? null,
            $data['image_alt_text'] ?? null,
            isset($data['image_width']) ? (int) $data['image_width'] : null,
            isset($data['image_height']) ? (int) $data['image_height'] : null
        );
    }
}
