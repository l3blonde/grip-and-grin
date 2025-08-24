<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Password;
use DateTime;

class ResetPasswordUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function generateResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return null; // Don't reveal if email exists
        }

        $token = bin2hex(random_bytes(32));
        $expires = new DateTime('+1 hour');

        $this->userRepository->setPasswordResetToken($user->getId(), $token, $expires);
        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->userRepository->findByPasswordResetToken($token);
        if (!$user) {
            return false;
        }

        $passwordVO = new Password($newPassword);

        $success = $this->userRepository->updatePassword($user->getId(), $passwordVO->getHashedValue());
        if ($success) {
            $this->userRepository->clearPasswordResetToken($user->getId());
        }

        return $success;
    }
}
