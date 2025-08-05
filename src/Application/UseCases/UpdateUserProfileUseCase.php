<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Email;
use InvalidArgumentException;

class UpdateUserProfileUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(
        int $userId,
        string $username,
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $bio = null
    ): User {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $username = trim($username);
        if (empty($username) || strlen($username) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters long');
        }

        $emailVO = new Email($email);

        $existingUserByUsername = $this->userRepository->findByUsername($username);
        if ($existingUserByUsername && $existingUserByUsername->getId() !== $userId) {
            throw new InvalidArgumentException('Username already exists');
        }

        $existingUserByEmail = $this->userRepository->findByEmail($emailVO->getValue());
        if ($existingUserByEmail && $existingUserByEmail->getId() !== $userId) {
            throw new InvalidArgumentException('Email already exists');
        }

        $updatedUser = new User(
            $userId,
            $username,
            $emailVO->getValue(),
            $user->getPasswordHash(),
            $user->getRole(),
            $user->getCreatedAt(),
            $user->isActive(),
            $user->isEmailVerified(),
            $firstName ? trim($firstName) : null,
            $lastName ? trim($lastName) : null,
            $bio ? trim($bio) : null,
            $user->getAvatarPath()
        );

        return $this->userRepository->save($updatedUser);
    }
}
