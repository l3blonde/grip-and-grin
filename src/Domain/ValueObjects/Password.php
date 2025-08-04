<?php
declare(strict_types=1);

namespace GripAndGrin\Domain\ValueObjects;

use InvalidArgumentException;

class Password
{
    private string $hashedValue;

    public function __construct(string $plainPassword)
    {
        if (strlen($plainPassword) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $plainPassword)) {
            throw new InvalidArgumentException('Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }

        $this->hashedValue = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public static function fromHash(string $hashedPassword): self
    {
        $instance = new self('TempPassword123'); // Temporary valid password for validation
        $instance->hashedValue = $hashedPassword;
        return $instance;
    }

    public function getHashedValue(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }
}
