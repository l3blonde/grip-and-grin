<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use DateTime;
use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\ArticleStatus;
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
            WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() 
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
            WHERE slug = :slug AND status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()
        ");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToArticle($row) : null;
    }

    public function findNextArticle(Article $currentArticle): ?Article
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE status = 'published' 
            AND published_at IS NOT NULL 
            AND published_at <= NOW()
            AND published_at > :current_published_at
            ORDER BY published_at ASC
            LIMIT 1
        ");
        $stmt->execute(['current_published_at' => $currentArticle->getPublishedAt()->format('Y-m-d H:i:s')]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToArticle($row) : null;
    }

    public function findPreviousArticle(Article $currentArticle): ?Article
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE status = 'published' 
            AND published_at IS NOT NULL 
            AND published_at <= NOW()
            AND published_at < :current_published_at
            ORDER BY published_at DESC
            LIMIT 1
        ");
        $stmt->execute(['current_published_at' => $currentArticle->getPublishedAt()->format('Y-m-d H:i:s')]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToArticle($row) : null;
    }

    public function findById(int $id): ?Article
    {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToArticle($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM articles ORDER BY created_at DESC");

        $articles = [];
        while ($row = $stmt->fetch()) {
            $articles[] = $this->mapRowToArticle($row);
        }
        return $articles;
    }

    public function findAllPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            ORDER BY created_at DESC
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

    public function countAll(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM articles");
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function findAllPublishedPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() 
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
            WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()
        ");
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function searchArticles(string $query, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM articles 
            WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()
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
            WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()
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
            AND status = 'published' AND published_at IS NOT NULL AND published_at <= NOW() 
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
            AND status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()
        ");
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    public function save(Article $article): Article
    {
        if ($article->getId() === 0) {
            return $this->insert($article);
        }
        return $this->update($article);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function insert(Article $article): Article
    {
        $stmt = $this->db->prepare("
            INSERT INTO articles (title, slug, content, excerpt, author_id, category_id, status, published_at, 
                                image_original_path, image_thumbnail_path, image_medium_path, image_full_path, 
                                image_alt_text, image_width, image_height, created_at) 
            VALUES (:title, :slug, :content, :excerpt, :author_id, :category_id, :status, :published_at,
                    :image_original_path, :image_thumbnail_path, :image_medium_path, :image_full_path,
                    :image_alt_text, :image_width, :image_height, :created_at)
        ");

        $featuredImage = $article->getFeaturedImage();

        $stmt->execute([
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'content' => $article->getContent(),
            'excerpt' => $article->getExcerpt(),
            'author_id' => $article->getAuthorId(),
            'category_id' => $article->getCategoryId(),
            'status' => $article->getStatus()->getValue(),
            'published_at' => $article->getPublishedAt()?->format('Y-m-d H:i:s'),
            'image_original_path' => $featuredImage?->getOriginalPath(),
            'image_thumbnail_path' => $featuredImage?->getThumbnailPath(),
            'image_medium_path' => $featuredImage?->getMediumPath(),
            'image_full_path' => $featuredImage?->getFullPath(),
            'image_alt_text' => $featuredImage?->getAltText(),
            'image_width' => $featuredImage?->getOriginalWidth(),
            'image_height' => $featuredImage?->getOriginalHeight(),
            'created_at' => $article->getCreatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = (int) $this->db->lastInsertId();

        return new Article(
            $id,
            $article->getTitle(),
            $article->getSlug(),
            $article->getContent(),
            $article->getExcerpt(),
            $article->getAuthorId(),
            $article->getCategoryId(),
            $article->getStatus(),
            $article->getPublishedAt(),
            $article->getCreatedAt(),
            $article->getFeaturedImage()
        );
    }

    private function update(Article $article): Article
    {
        $stmt = $this->db->prepare("
            UPDATE articles 
            SET title = :title, slug = :slug, content = :content, excerpt = :excerpt, 
                category_id = :category_id, status = :status, published_at = :published_at,
                image_original_path = :image_original_path, image_thumbnail_path = :image_thumbnail_path,
                image_medium_path = :image_medium_path, image_full_path = :image_full_path,
                image_alt_text = :image_alt_text, image_width = :image_width, image_height = :image_height
            WHERE id = :id
        ");

        $featuredImage = $article->getFeaturedImage();

        $stmt->execute([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'content' => $article->getContent(),
            'excerpt' => $article->getExcerpt(),
            'category_id' => $article->getCategoryId(),
            'status' => $article->getStatus()->getValue(),
            'published_at' => $article->getPublishedAt()?->format('Y-m-d H:i:s'),
            'image_original_path' => $featuredImage?->getOriginalPath(),
            'image_thumbnail_path' => $featuredImage?->getThumbnailPath(),
            'image_medium_path' => $featuredImage?->getMediumPath(),
            'image_full_path' => $featuredImage?->getFullPath(),
            'image_alt_text' => $featuredImage?->getAltText(),
            'image_width' => $featuredImage?->getOriginalWidth(),
            'image_height' => $featuredImage?->getOriginalHeight()
        ]);

        return $article;
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
            $row['excerpt'] ?? '',
            (int)$row['author_id'],
            (int)$row['category_id'],
            new ArticleStatus($row['status']),
            $row['published_at'] ? new DateTime($row['published_at']) : null,
            new DateTime($row['created_at']),
            $featuredImage
        );
    }
}
