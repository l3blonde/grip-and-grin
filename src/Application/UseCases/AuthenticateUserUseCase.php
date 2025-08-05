<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use InvalidArgumentException;

class AuthenticateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $emailOrUsername, string $password): User
    {
        $user = $this->userRepository->findByEmail($emailOrUsername);
        if (!$user) {
            $user = $this->userRepository->findByUsername($emailOrUsername);
        }

        if (!$user) {
            throw new InvalidArgumentException('Invalid credentials');
        }

        if (!$user->isActive()) {
            throw new InvalidArgumentException('Account is deactivated');
        }

        if (!$user->verifyPassword($password)) {
            throw new InvalidArgumentException('Invalid credentials');
        }

        return $user;
    }
}
