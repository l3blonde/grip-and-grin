<?php

declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\Entities\User;

class AuthenticateUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(string $emailOrUsername, string $password): ?User
    {
        // Try to find user by email first, then username
        $user = $this->userRepository->findByEmail($emailOrUsername);
        if (!$user) {
            $user = $this->userRepository->findByUsername($emailOrUsername);
        }

        if (!$user || !$user->isActive()) {
            return null;
        }

        if (!$user->verifyPassword($password)) {
            return null;
        }

        return $user;
    }
}
