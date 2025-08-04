<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\ValueObjects;

class Image
{
    public function __construct(
        private readonly string $originalPath,
        private readonly string $thumbnailPath,
        private readonly string $mediumPath,
        private readonly string $fullPath,
        private readonly string $altText,
        private readonly int $originalWidth,
        private readonly int $originalHeight
    ) {}

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function getThumbnailPath(): string
    {
        return $this->thumbnailPath;
    }

    public function getMediumPath(): string
    {
        return $this->mediumPath;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }

    public function getOriginalWidth(): int
    {
        return $this->originalWidth;
    }

    public function getOriginalHeight(): int
    {
        return $this->originalHeight;
    }

    public function toArray(): array
    {
        return [
            'original' => $this->originalPath,
            'thumbnail' => $this->thumbnailPath,
            'medium' => $this->mediumPath,
            'full' => $this->fullPath,
            'alt' => $this->altText,
            'width' => $this->originalWidth,
            'height' => $this->originalHeight,
        ];
    }
}
