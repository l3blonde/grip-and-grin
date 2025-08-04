<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Entities;

use DateTime;

class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $username,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly DateTime $createdAt,
        private readonly bool $isActive = true
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}
