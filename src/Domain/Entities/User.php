<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\Entities;

use DateTime;
use GripAndGrin\Domain\ValueObjects\UserRole;

class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $username,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly UserRole $role,
        private readonly DateTime $createdAt,
        private readonly bool $isActive = true,
        private readonly bool $emailVerified = false,
        private readonly ?string $firstName = null,
        private readonly ?string $lastName = null,
        private readonly ?string $bio = null,
        private readonly ?string $avatarPath = null
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

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        if ($this->firstName && $this->lastName) {
            return $this->firstName . ' ' . $this->lastName;
        }
        return $this->firstName ?? $this->lastName ?? $this->username;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function canManageArticles(): bool
    {
        return $this->role->canManageArticles();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }
}
