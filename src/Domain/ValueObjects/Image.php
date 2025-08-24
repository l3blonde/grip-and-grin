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

    public function getAspectRatio(): float
    {
        if ($this->originalHeight === 0) {
            return 1.0;
        }
        
        return $this->originalWidth / $this->originalHeight;
    }

    public function isLandscape(): bool
    {
        return $this->originalWidth > $this->originalHeight;
    }

    public function isPortrait(): bool
    {
        return $this->originalHeight > $this->originalWidth;
    }

    public function isSquare(): bool
    {
        return $this->originalWidth === $this->originalHeight;
    }
}
