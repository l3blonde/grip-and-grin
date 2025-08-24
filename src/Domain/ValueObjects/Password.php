<?php

declare(strict_types=1);

namespace GripAndGrin\Domain\ValueObjects;

use InvalidArgumentException;

class Password
{
    private readonly string $hashedValue;

    public function __construct(string $plainPassword)
    {
        $this->validatePassword($plainPassword);
        $this->hashedValue = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public static function fromHash(string $hashedPassword): self
    {
        // Create instance without validation since hash is already validated
        $instance = new class($hashedPassword) extends Password {
            public function __construct(string $hashedPassword) {
                // Skip validation and directly set the hash
                $this->hashedValue = $hashedPassword;
            }
        };
        return $instance;
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one uppercase letter');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one lowercase letter');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one number');
        }
    }

    public function getHash(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }
}
