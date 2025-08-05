<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\ValueObjects;

use InvalidArgumentException;

class ArticleStatus
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';

    private string $value;

    public function __construct(string $status)
    {
        if (!in_array($status, [self::DRAFT, self::PUBLISHED, self::ARCHIVED])) {
            throw new InvalidArgumentException('Invalid article status');
        }
        $this->value = $status;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->value === self::PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->value === self::ARCHIVED;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
