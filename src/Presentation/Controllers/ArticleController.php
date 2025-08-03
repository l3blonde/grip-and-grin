<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\GetArticleBySlugUseCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ArticleController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GetArticleBySlugUseCase $getArticleBySlugUseCase
    ) {}

    public function show(string $slug): Response
    {
        $article = $this->getArticleBySlugUseCase->execute($slug);

        if (!$article) {
            return new Response($this->twig->render('404.html.twig'), Response::HTTP_NOT_FOUND);
        }

        $content = $this->twig->render('article-detail.html.twig', ['article' => $article]);
        return new Response($content);
    }
}
