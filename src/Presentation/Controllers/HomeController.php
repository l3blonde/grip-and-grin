<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\GetPaginatedArticlesUseCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class HomeController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GetPaginatedArticlesUseCase $getPaginatedArticlesUseCase
    ) {}

    public function show(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->getPaginatedArticlesUseCase->execute($page);
        $content = $this->twig->render('home.html.twig', $result);
        return new Response($content);
    }
}
