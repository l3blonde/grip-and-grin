<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use GripAndGrin\Domain\Entities\Category;
use GripAndGrin\Domain\Interfaces\CategoryRepositoryInterface;
use GripAndGrin\Infrastructure\Database\DatabaseConnection;
use PDO;

class PDOCategoryRepository implements CategoryRepositoryInterface
{
    private PDO $db;

    public function __construct(DatabaseConnection $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");

        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[] = $this->mapRowToCategory($row);
        }
        return $categories;
    }

    public function findBySlug(string $slug): ?Category
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToCategory($row) : null;
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToCategory($row) : null;
    }

    private function mapRowToCategory(array $row): Category
    {
        return new Category(
            (int)$row['id'],
            $row['name'],
            $row['slug'],
            $row['description'] ?? ''
        );
    }
}
