<?php

declare(strict_types=1);

namespace GripAndGrin\Domain\Interfaces;

use GripAndGrin\Domain\Entities\User;

interface UserRepositoryInterface
{
    // Read-only methods for authentication
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByUsername(string $username): ?User;

    // Keep password reset for admin accounts only
    public function findByPasswordResetToken(string $token): ?User;
    public function setPasswordResetToken(int $userId, string $token, \DateTime $expires): bool;
    public function clearPasswordResetToken(int $userId): bool;
    public function updatePassword(int $userId, string $newPasswordHash): bool;

    public function updateEmail(int $userId, string $newEmail): bool;
    public function emailExists(string $email): bool;

    public function save(User $user): ?User;
    public function deactivateUser(int $userId): bool;
    public function findAllByRole(string $role): array;
    public function getUserActivityLog(int $userId): array;
}
