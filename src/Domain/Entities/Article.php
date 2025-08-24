<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Entities;

use DateTime;

class Article
{
    private int $id;
    private string $title;
    private string $slug;
    private string $content;
    private ?string $excerpt;
    private int $authorId;
    private int $categoryId;
    private string $status;
    private ?DateTime $publishedAt;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?string $featuredImage;
    private ?string $imageThumbnailPath;
    private ?string $imageMediumPath;
    private ?string $imageFullPath;
    private ?string $imageAltText;
    private ?int $imageWidth;
    private ?int $imageHeight;

    public function __construct(
        int $id,
        string $title,
        string $slug,
        string $content,
        ?string $excerpt,
        int $authorId,
        int $categoryId,
        string $status = 'draft',
        ?DateTime $publishedAt = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        ?string $featuredImage = null,
        ?string $imageThumbnailPath = null,
        ?string $imageMediumPath = null,
        ?string $imageFullPath = null,
        ?string $imageAltText = null,
        ?int $imageWidth = null,
        ?int $imageHeight = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->slug = $slug;
        $this->content = $content;
        $this->excerpt = $excerpt;
        $this->authorId = $authorId;
        $this->categoryId = $categoryId;
        $this->status = $status;
        $this->publishedAt = $publishedAt;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
        $this->featuredImage = $featuredImage;
        $this->imageThumbnailPath = $imageThumbnailPath;
        $this->imageMediumPath = $imageMediumPath;
        $this->imageFullPath = $imageFullPath;
        $this->imageAltText = $imageAltText;
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
    }

    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getSlug(): string { return $this->slug; }
    public function getContent(): string { return $this->content; }
    public function getExcerpt(): ?string { return $this->excerpt; }
    public function getAuthorId(): int { return $this->authorId; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getStatus(): string { return $this->status; }
    public function getPublishedAt(): ?DateTime { return $this->publishedAt; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }
    public function getFeaturedImage(): ?string { return $this->featuredImage; }
    public function getImageThumbnailPath(): ?string { return $this->imageThumbnailPath; }
    public function getImageMediumPath(): ?string { return $this->imageMediumPath; }
    public function getImageFullPath(): ?string { return $this->imageFullPath; }
    public function getImageAltText(): ?string { return $this->imageAltText; }
    public function getImageWidth(): ?int { return $this->imageWidth; }
    public function getImageHeight(): ?int { return $this->imageHeight; }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->publishedAt !== null;
    }

    public function hasFeaturedImage(): bool
    {
        return $this->featuredImage !== null || $this->imageThumbnailPath !== null;
    }

    public function getDisplayExcerpt(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }
        
        $plainText = strip_tags($this->content);
        return strlen($plainText) > 200 ? substr($plainText, 0, 200) . '...' : $plainText;
    }
}
