<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use InvalidArgumentException;

class GetUserProfileUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $userId): User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        return $user;
    }
}
