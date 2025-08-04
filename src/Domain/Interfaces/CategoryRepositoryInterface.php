<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Interfaces;

use GripAndGrin\Domain\Entities\Category;

interface CategoryRepositoryInterface
{
    /**
     * @return Category[]
     */
    public function findAll(): array;

    public function findBySlug(string $slug): ?Category;

    public function findById(int $id): ?Category;
}
