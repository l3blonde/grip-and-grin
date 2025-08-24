<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use GripAndGrin\Domain\Entities\Category;
use GripAndGrin\Domain\Interfaces\CategoryRepositoryInterface;
use PDO;
use DateTime;

class PDOCategoryRepository implements CategoryRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findBySlug(string $slug): ?Category
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name");
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function mapToEntity(array $data): Category
    {
        return new Category(
            (int) $data['id'],
            $data['name'],
            $data['slug'],
            $data['description'] ?? '',
            $data['created_at'] ? new DateTime($data['created_at']) : new DateTime(),
            $data['updated_at'] ? new DateTime($data['updated_at']) : new DateTime()
        );
    }
}
