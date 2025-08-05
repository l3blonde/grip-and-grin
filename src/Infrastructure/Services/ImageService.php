<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Services;

use GripAndGrin\Domain\ValueObjects\Image;
use InvalidArgumentException;
use RuntimeException;

class ImageService
{
    private const THUMBNAIL_SIZE = ['width' => 300, 'height' => 200];
    private const MEDIUM_SIZE = ['width' => 800, 'height' => 600];
    private const FULL_SIZE = ['width' => 1200, 'height' => 800];

    private const UPLOAD_DIR = '/var/www/html/public/uploads/';
    private const PUBLIC_PATH = '/uploads/';

    public function __construct()
    {
        $this->ensureUploadDirectoryExists();
    }

    public function processUploadedImage(array $uploadedFile, string $altText = ''): Image
    {
        $this->validateUploadedFile($uploadedFile);

        $originalFilename = $this->generateUniqueFilename($uploadedFile['name']);
        $originalPath = self::UPLOAD_DIR . 'originals/' . $originalFilename;

        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $originalPath)) {
            throw new RuntimeException('Failed to move uploaded file');
        }

        // Get original dimensions
        $imageInfo = getimagesize($originalPath);
        if (!$imageInfo) {
            unlink($originalPath);
            throw new InvalidArgumentException('Invalid image file');
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];

        $thumbnailPath = $this->createResizedImage($originalPath, 'thumbnails/', self::THUMBNAIL_SIZE);
        $mediumPath = $this->createResizedImage($originalPath, 'medium/', self::MEDIUM_SIZE);
        $fullPath = $this->createResizedImage($originalPath, 'full/', self::FULL_SIZE);

        return new Image(
            self::PUBLIC_PATH . 'originals/' . $originalFilename,
            self::PUBLIC_PATH . $thumbnailPath,
            self::PUBLIC_PATH . $mediumPath,
            self::PUBLIC_PATH . $fullPath,
            $altText,
            $originalWidth,
            $originalHeight
        );
    }

    private function createResizedImage(string $sourcePath, string $subDir, array $targetSize): string
    {
        $pathInfo = pathinfo($sourcePath);
        $filename = $pathInfo['filename'];

        // Create WebP version
        $webpFilename = $filename . '.webp';
        $webpPath = self::UPLOAD_DIR . $subDir . $webpFilename;

        // Create JPEG fallback
        $jpegFilename = $filename . '.jpg';
        $jpegPath = self::UPLOAD_DIR . $subDir . $jpegFilename;

        $this->ensureDirectoryExists(self::UPLOAD_DIR . $subDir);

        $sourceImage = $this->loadImage($sourcePath);
        if (!$sourceImage) {
            throw new RuntimeException('Failed to load source image');
        }

        $sourceDimensions = [imagesx($sourceImage), imagesy($sourceImage)];
        $targetDimensions = $this->calculateAspectRatioFit($sourceDimensions, $targetSize);

        $resizedImage = imagecreatetruecolor($targetDimensions['width'], $targetDimensions['height']);

        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);

        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $targetDimensions['width'], $targetDimensions['height'],
            $sourceDimensions[0], $sourceDimensions[1]
        );

        if (function_exists('imagewebp')) {
            imagewebp($resizedImage, $webpPath, 85);
        }

        imagejpeg($resizedImage, $jpegPath, 85);

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $subDir . $webpFilename;
    }

    private function loadImage(string $path)
    {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return false;
        }

        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    private function calculateAspectRatioFit(array $srcSize, array $maxSize): array
    {
        $ratio = min($maxSize['width'] / $srcSize[0], $maxSize['height'] / $srcSize[1]);

        return [
            'width' => (int)($srcSize[0] * $ratio),
            'height' => (int)($srcSize[1] * $ratio)
        ];
    }

    private function validateUploadedFile(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload error: ' . $file['error']);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new InvalidArgumentException('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('File too large. Maximum size is 10MB.');
        }
    }

    private function generateUniqueFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return uniqid('img_', true) . '.' . $extension;
    }

    private function ensureUploadDirectoryExists(): void
    {
        $directories = [
            self::UPLOAD_DIR,
            self::UPLOAD_DIR . 'originals/',
            self::UPLOAD_DIR . 'thumbnails/',
            self::UPLOAD_DIR . 'medium/',
            self::UPLOAD_DIR . 'full/'
        ];

        foreach ($directories as $dir) {
            $this->ensureDirectoryExists($dir);
        }
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new RuntimeException("Failed to create directory: $path");
            }
        }
    }

    public function deleteImage(Image $image): void
    {
        $paths = [
            self::UPLOAD_DIR . 'originals/' . basename($image->getOriginalPath()),
            self::UPLOAD_DIR . 'thumbnails/' . basename($image->getThumbnailPath()),
            self::UPLOAD_DIR . 'medium/' . basename($image->getMediumPath()),
            self::UPLOAD_DIR . 'full/' . basename($image->getFullPath())
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
