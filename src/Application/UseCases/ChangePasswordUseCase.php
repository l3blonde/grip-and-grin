<?php

declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Password;
use InvalidArgumentException;

class ChangePasswordUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        if (!$user->verifyPassword($currentPassword)) {
            throw new InvalidArgumentException('Current password is incorrect');
        }

        $newPasswordVO = new Password($newPassword);

        return $this->userRepository->updatePassword($userId, $newPasswordVO->getHash());
    }
}
