<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\GetArticlesUseCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class HomeController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GetArticlesUseCase $getArticlesUseCase
    ) {}

    public function show(): Response
    {
        $articles = $this->getArticlesUseCase->execute();
        $content = $this->twig->render('home.html.twig', ['articles' => $articles]);
        return new Response($content);
    }
}
