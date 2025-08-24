<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;

class DeactivateUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        if ($user->getRole() === 'admin') {
            throw new \InvalidArgumentException('Cannot deactivate admin users');
        }

        return $this->userRepository->deactivateUser($userId);
    }
}
