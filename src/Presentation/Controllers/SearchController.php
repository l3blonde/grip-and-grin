<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\SearchArticlesUseCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class SearchController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SearchArticlesUseCase $searchArticlesUseCase
    ) {}

    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));

        $result = $this->searchArticlesUseCase->execute($query, $page);

        $content = $this->twig->render('search-results.html.twig', $result);
        return new Response($content);
    }
}
