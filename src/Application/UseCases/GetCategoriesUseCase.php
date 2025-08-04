<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\CategoryRepositoryInterface;

class GetCategoriesUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    public function execute(): array
    {
        return $this->categoryRepository->findAll();
    }
}
