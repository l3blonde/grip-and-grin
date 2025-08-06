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
        $result = $this->getArticleBySlugUseCase->execute($slug);

        if (!$result['article']) {
            return new Response($this->twig->render('404.html.twig'), Response::HTTP_NOT_FOUND);
        }

        $content = $this->twig->render('article-detail.html.twig', [
            'article' => $result['article'],
            'nextArticle' => $result['nextArticle'],
            'previousArticle' => $result['previousArticle']
        ]);

        return new Response($content);
    }
}
