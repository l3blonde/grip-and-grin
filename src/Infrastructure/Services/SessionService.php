<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Services;

use GripAndGrin\Domain\Entities\User;

class SessionService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(User $user): void
    {
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['logged_in'] = true;

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();

        // Start new session
        session_start();
        session_regenerate_id(true);
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUsername(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public function getCurrentUserEmail(): ?string
    {
        return $_SESSION['email'] ?? null;
    }

    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
