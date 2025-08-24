<?php

declare(strict_types=1);

namespace GripAndGrin\Domain\Entities;

use DateTime;

class User
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $role;
    private bool $isActive;
    private bool $emailVerified;
    private ?string $emailVerificationToken;
    private ?string $passwordResetToken;
    private ?DateTime $passwordResetExpires;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $bio;
    private ?string $avatarPath;
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        string $username,
        string $email,
        string $passwordHash,
        string $role = 'user',
        bool $isActive = true,
        bool $emailVerified = false,
        ?string $emailVerificationToken = null,
        ?string $passwordResetToken = null,
        ?DateTime $passwordResetExpires = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $bio = null,
        ?string $avatarPath = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->isActive = $isActive;
        $this->emailVerified = $emailVerified;
        $this->emailVerificationToken = $emailVerificationToken;
        $this->passwordResetToken = $passwordResetToken;
        $this->passwordResetExpires = $passwordResetExpires;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->bio = $bio;
        $this->avatarPath = $avatarPath;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function isActive(): bool { return $this->isActive; }
    public function isEmailVerified(): bool { return $this->emailVerified; }
    public function getEmailVerificationToken(): ?string { return $this->emailVerificationToken; }
    public function getPasswordResetToken(): ?string { return $this->passwordResetToken; }
    public function getPasswordResetExpires(): ?DateTime { return $this->passwordResetExpires; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getBio(): ?string { return $this->bio; }
    public function getAvatarPath(): ?string { return $this->avatarPath; }
    public function getCreatedAt(): ?DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    public function getFullName(): string
    {
        $parts = array_filter([$this->firstName, $this->lastName]);
        return empty($parts) ? $this->username : implode(' ', $parts);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function canManageArticles(): bool
    {
        return $this->isEditor();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}
