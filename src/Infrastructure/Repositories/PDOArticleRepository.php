<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use DateTime;
use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Image;
use GripAndGrin\Infrastructure\Database\DatabaseConnection;
use PDO;

class PDOArticleRepository implements ArticleRepositoryInterface
{
    private PDO $db;

    public function __construct(DatabaseConnection $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function findAllPublished(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM articles 
            WHERE published_at IS NOT NULL AND published_at <= NOW() 
            ORDER BY published_at DESC
        ");

        $articles = [];
        while ($row = $stmt->fetch()) {
            $articles[] = $this->mapRowToArticle($row);
        }
        return $articles;
    }

    public function findBySlug(string $slug): ?Article
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE slug = :slug AND published_at IS NOT NULL AND published_at <= NOW()
        ");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToArticle($row) : null;
    }

    public function findAllPublishedPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE published_at IS NOT NULL AND published_at <= NOW() 
            ORDER BY published_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $articles = [];
        while ($row = $stmt->fetch()) {
            $articles[] = $this->mapRowToArticle($row);
        }
        return $articles;
    }

    public function countAllPublished(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM articles 
            WHERE published_at IS NOT NULL AND published_at <= NOW()
        ");
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function searchArticles(string $query, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE published_at IS NOT NULL AND published_at <= NOW()
            AND (title LIKE :query OR content LIKE :query)
            ORDER BY published_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $searchTerm = '%' . $query . '%';
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $articles = [];
        while ($row = $stmt->fetch()) {
            $articles[] = $this->mapRowToArticle($row);
        }
        return $articles;
    }

    public function countSearchResults(string $query): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM articles 
            WHERE published_at IS NOT NULL AND published_at <= NOW()
            AND (title LIKE :query OR content LIKE :query)
        ");
        $searchTerm = '%' . $query . '%';
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function findByCategoryPaginated(int $categoryId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE category_id = :category_id 
            AND published_at IS NOT NULL AND published_at <= NOW() 
            ORDER BY published_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $articles = [];
        while ($row = $stmt->fetch()) {
            $articles[] = $this->mapRowToArticle($row);
        }
        return $articles;
    }

    public function countByCategory(int $categoryId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM articles 
            WHERE category_id = :category_id 
            AND published_at IS NOT NULL AND published_at <= NOW()
        ");
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    private function mapRowToArticle(array $row): Article
    {
        $featuredImage = null;

        // Check if article has image data
        if (!empty($row['image_thumbnail_path'])) {
            $featuredImage = new Image(
                $row['image_original_path'] ?? '',
                $row['image_thumbnail_path'],
                $row['image_medium_path'] ?? '',
                $row['image_full_path'] ?? '',
                $row['image_alt_text'] ?? '',
                (int)($row['image_width'] ?? 0),
                (int)($row['image_height'] ?? 0)
            );
        }

        return new Article(
            (int)$row['id'],
            $row['title'],
            $row['slug'],
            $row['content'],
            (int)$row['author_id'],
            (int)$row['category_id'],
            $row['published_at'] ? new DateTime($row['published_at']) : null,
            new DateTime($row['created_at']),
            $featuredImage
        );
    }
}
