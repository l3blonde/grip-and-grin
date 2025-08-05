<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\ValueObjects;

use InvalidArgumentException;

class UserRole
{
    public const ADMIN = 'admin';
    public const EDITOR = 'editor';
    public const USER = 'user';

    private string $value;

    public function __construct(string $role)
    {
        if (!in_array($role, [self::ADMIN, self::EDITOR, self::USER])) {
            throw new InvalidArgumentException('Invalid user role');
        }
        $this->value = $role;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isEditor(): bool
    {
        return $this->value === self::EDITOR;
    }

    public function isUser(): bool
    {
        return $this->value === self::USER;
    }

    public function canManageArticles(): bool
    {
        return in_array($this->value, [self::ADMIN, self::EDITOR]);
    }

    public function canManageUsers(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
