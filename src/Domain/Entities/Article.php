<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Entities;

use DateTime;
use GripAndGrin\Domain\ValueObjects\ArticleStatus;
use GripAndGrin\Domain\ValueObjects\Image;

class Article
{
    public function __construct(
        private readonly int $id,
        private readonly string $title,
        private readonly string $slug,
        private readonly string $content,
        private readonly string $excerpt,
        private readonly int $authorId,
        private readonly int $categoryId,
        private readonly ArticleStatus $status,
        private readonly ?DateTime $publishedAt,
        private readonly DateTime $createdAt,
        private readonly ?Image $featuredImage = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }

    public function getPublishedAt(): ?DateTime
    {
        return $this->publishedAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getFeaturedImage(): ?Image
    {
        return $this->featuredImage;
    }

    public function hasFeaturedImage(): bool
    {
        return $this->featuredImage !== null;
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }
}
