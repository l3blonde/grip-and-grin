<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\GetArticlesByCategoryUseCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class CategoryController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GetArticlesByCategoryUseCase $getArticlesByCategoryUseCase
    ) {}

    public function show(string $categorySlug, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->getArticlesByCategoryUseCase->execute($categorySlug, $page);

        if (!$result['category']) {
            return new Response($this->twig->render('404.html.twig'), Response::HTTP_NOT_FOUND);
        }

        $content = $this->twig->render('category.html.twig', $result);
        return new Response($content);
    }
}
