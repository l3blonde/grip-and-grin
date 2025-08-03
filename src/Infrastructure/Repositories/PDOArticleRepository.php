<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use DateTime;
use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
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

    private function mapRowToArticle(array $row): Article
    {
        return new Article(
            (int)$row['id'],
            $row['title'],
            $row['slug'],
            $row['content'],
            (int)$row['author_id'],
            (int)$row['category_id'],
            $row['published_at'] ? new DateTime($row['published_at']) : null,
            new DateTime($row['created_at'])
        );
    }
}
