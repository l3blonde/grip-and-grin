<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Entities;

class Category
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly string $description
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
