<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Interfaces;

use GripAndGrin\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function findAll(): array;

    public function save(User $user): User;

    public function emailExists(string $email): bool;

    public function usernameExists(string $username): bool;
}
