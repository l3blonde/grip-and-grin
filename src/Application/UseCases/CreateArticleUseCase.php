<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use DateTime;
use GripAndGrin\Domain\Entities\Article;
use GripAndGrin\Domain\Interfaces\ArticleRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\ArticleStatus;
use GripAndGrin\Domain\ValueObjects\Image;
use GripAndGrin\Infrastructure\Services\ImageService;
use InvalidArgumentException;

class CreateArticleUseCase
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ImageService $imageService
    ) {}
    public function execute(
        string $title,
        string $content,
        string $excerpt,
        int $authorId,
        int $categoryId,
        string $status = ArticleStatus::DRAFT,
        ?array $uploadedImage = null,
        ?string $imageAltText = null
    ): Article {

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

        $slug = $this->generateSlug($title);

        if ($this->articleRepository->findBySlug($slug)) {
            $slug = $slug . '-' . time();
        }

        $featuredImage = null;
        if ($uploadedImage && $uploadedImage['error'] === UPLOAD_ERR_OK) {
            $featuredImage = $this->imageService->processUploadedImage($uploadedImage, $imageAltText ?? $title);
        }

        $articleStatus = new ArticleStatus($status);

        $publishedAt = $articleStatus->isPublished() ? new DateTime() : null;

        $article = new Article(
            0,
            $title,
            $slug,
            $content,
            $excerpt,
            $authorId,
            $categoryId,
            $articleStatus,
            $publishedAt,
            new DateTime(),
            $featuredImage
        );

        return $this->articleRepository->save($article);
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
