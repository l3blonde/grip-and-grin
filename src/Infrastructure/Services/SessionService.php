<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Services;

use GripAndGrin\Domain\Entities\User;

class SessionService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
        }
    }

    public function login(User $user): void
    {
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole()->getValue();
        $_SESSION['is_admin'] = $user->isAdmin();
        $_SESSION['is_editor'] = $user->isEditor();
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        session_regenerate_id(true);
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getCurrentUserId(): ?int { return $_SESSION['user_id'] ?? null; }
    public function getCurrentUsername(): ?string { return $_SESSION['username'] ?? null; }
    public function getCurrentUserEmail(): ?string { return $_SESSION['email'] ?? null; }
    public function getCurrentUserRole(): ?string { return $_SESSION['role'] ?? null; }
    public function isAdmin(): bool { return $_SESSION['is_admin'] ?? false; }
    public function isEditor(): bool { return $_SESSION['is_editor'] ?? false; }

    public function setFlashMessage(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    public function getFlashMessage(string $type): ?string
    {
        $message = $_SESSION['flash'][$type] ?? null;
        if ($message) unset($_SESSION['flash'][$type]);
        return $message;
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

    public function getSessionData(): array
    {
        return [
            'logged_in' => $this->isLoggedIn(),
            'user_id' => $this->getCurrentUserId(),
            'username' => $this->getCurrentUsername(),
            'email' => $this->getCurrentUserEmail(),
            'role' => $this->getCurrentUserRole(),
            'is_admin' => $this->isAdmin(),
            'is_editor' => $this->isEditor(),
            'flash' => [
                'success' => $this->getFlashMessage('success'),
                'error' => $this->getFlashMessage('error'),
                'info' => $this->getFlashMessage('info'),
                'warning' => $this->getFlashMessage('warning')
            ]
        ];
    }
}
