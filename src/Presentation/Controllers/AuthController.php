<?php

declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\AuthenticateUserUseCase;
use GripAndGrin\Infrastructure\Repositories\PDOUserRepository;
use PDO;

class AuthController
{
    private AuthenticateUserUseCase $authenticateUserUseCase;
    private int $maxLoginAttempts = 5;
    private int $lockoutTime = 900; // 15 minutes

    public function __construct(PDO $pdo)
    {
        $userRepository = new PDOUserRepository($pdo);
        $this->authenticateUserUseCase = new AuthenticateUserUseCase($userRepository);
    }

    public function showLogin(): array
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header("Location: /admin-dashboard");
            exit;
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return [
            'title' => 'Admin Login - Grip & Grin',
            'error' => $_SESSION['flash']['error'] ?? null,
            'csrf_token' => $_SESSION['csrf_token']
        ];
    }

    public function login(): array
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showLogin();
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            return [
                'title' => 'Admin Login - Grip & Grin',
                'error' => 'Invalid security token. Please try again.',
                'csrf_token' => $_SESSION['csrf_token']
            ];
        }

        if ($this->isRateLimited()) {
            return [
                'title' => 'Admin Login - Grip & Grin',
                'error' => 'Too many login attempts. Please try again in 15 minutes.',
                'csrf_token' => $_SESSION['csrf_token']
            ];
        }

        $emailOrUsername = filter_var(trim($_POST['email'] ?? $_POST['email_or_username'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($emailOrUsername) || empty($password)) {
            $this->recordFailedAttempt();
            return [
                'title' => 'Admin Login - Grip & Grin',
                'error' => 'Please enter both email/username and password',
                'email' => $emailOrUsername,
                'csrf_token' => $_SESSION['csrf_token']
            ];
        }

        $user = $this->authenticateUserUseCase->execute($emailOrUsername, $password);

        if (!$user) {
            $this->recordFailedAttempt();
            error_log("[v0] Login failed for: " . $emailOrUsername);
            return [
                'title' => 'Admin Login - Grip & Grin',
                'error' => 'Invalid credentials',
                'email_or_username' => $emailOrUsername,
                'csrf_token' => $_SESSION['csrf_token']
            ];
        }

        if (!$user->isAdmin() && $user->getRole() !== 'editor') {
            return [
                'title' => 'Admin Login - Grip & Grin',
                'error' => 'Access denied. Admin or editor privileges required.',
                'csrf_token' => $_SESSION['csrf_token']
            ];
        }

        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['is_admin'] = $user->isAdmin();

        unset($_SESSION['failed_attempts'], $_SESSION['last_attempt_time']);

        error_log("[v0] Login successful, redirecting to admin dashboard");
        header("Location: /admin-dashboard");
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: /");
        exit;
    }

    private function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function isRateLimited(): bool
    {
        $attempts = $_SESSION['failed_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_attempt_time'] ?? 0;

        if ($attempts >= $this->maxLoginAttempts) {
            return (time() - $lastAttempt) < $this->lockoutTime;
        }

        return false;
    }

    private function recordFailedAttempt(): void
    {
        $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt_time'] = time();
    }
}
