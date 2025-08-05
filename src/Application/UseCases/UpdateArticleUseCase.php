<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use DateTime;
use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\ArticleStatus;
use GripAndGrin\Infrastructure\Services\ImageService;
use InvalidArgumentException;

class UpdateArticleUseCase
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ImageService $imageService
    ) {}

    public function execute(
        int $articleId,
        string $title,
        string $content,
        string $excerpt,
        int $categoryId,
        string $status,
        ?array $uploadedImage = null,
        ?string $imageAltText = null,
        bool $removeExistingImage = false
    ): Article {
        // Find existing article
        $existingArticle = $this->articleRepository->findById($articleId);
        if (!$existingArticle) {
            throw new InvalidArgumentException('Article not found');
        }

        // Validate input
        $title = trim($title);
        $content = trim($content);
        $excerpt = trim($excerpt);

        if (empty($title)) {
            throw new InvalidArgumentException('Title is required');
        }

        if (empty($content)) {
            throw new InvalidArgumentException('Content is required');
        }

        if (strlen($excerpt) > 500) {
            throw new InvalidArgumentException('Excerpt must be 500 characters or less');
        }

        // Generate new slug if title changed
        $slug = $existingArticle->getSlug();
        if ($title !== $existingArticle->getTitle()) {
            $newSlug = $this->generateSlug($title);
            $existingSlugArticle = $this->articleRepository->findBySlug($newSlug);
            if ($existingSlugArticle && $existingSlugArticle->getId() !== $articleId) {
                $newSlug = $newSlug . '-' . time();
            }
            $slug = $newSlug;
        }

        // Handle image updates
        $featuredImage = $existingArticle->getFeaturedImage();

        if ($removeExistingImage && $featuredImage) {
            $this->imageService->deleteImage($featuredImage);
            $featuredImage = null;
        }

        if ($uploadedImage && $uploadedImage['error'] === UPLOAD_ERR_OK) {
            // Delete old image if exists
            if ($featuredImage) {
                $this->imageService->deleteImage($featuredImage);
            }
            $featuredImage = $this->imageService->processUploadedImage($uploadedImage, $imageAltText ?? $title);
        }

        // Create article status
        $articleStatus = new ArticleStatus($status);

        // Update published date if status changed to published
        $publishedAt = $existingArticle->getPublishedAt();
        if ($articleStatus->isPublished() && !$publishedAt) {
            $publishedAt = new DateTime();
        } elseif (!$articleStatus->isPublished()) {
            $publishedAt = null;
        }

        // Create updated article
        $updatedArticle = new Article(
            $articleId,
            $title,
            $slug,
            $content,
            $excerpt,
            $existingArticle->getAuthorId(),
            $categoryId,
            $articleStatus,
            $publishedAt,
            $existingArticle->getCreatedAt(),
            $featuredImage
        );

        return $this->articleRepository->save($updatedArticle);
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
