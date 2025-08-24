<?php

declare(strict_types=1);

namespace GripAndGrin\Domain\ValueObjects;

use InvalidArgumentException;

class Email
{
    private readonly string $value;

    public function __construct(string $email)
    {
        $this->validateEmail($email);
        $this->value = strtolower(trim($email));
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (strlen($email) > 254) {
            throw new InvalidArgumentException('Email address too long');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
