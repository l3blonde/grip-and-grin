<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\GetArticleBySlugUseCase;
use GripAndGrin\Infrastructure\Repositories\PDOArticleRepository;
use PDO;

class ArticleController
{
    private GetArticleBySlugUseCase $getArticleBySlugUseCase;

    public function __construct(PDO $pdo)
    {
        $articleRepository = new PDOArticleRepository($pdo);
        $this->getArticleBySlugUseCase = new GetArticleBySlugUseCase($articleRepository);
    }

    public function show(string $slug): ?array
    {
        $result = $this->getArticleBySlugUseCase->execute($slug);
        
        if (!$result['article']) {
            return null;
        }

        return [
            'article' => $result['article'],
            'nextArticle' => $result['nextArticle'],
            'previousArticle' => $result['previousArticle']
        ];
    }
}
