<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use PDO;
use GripAndGrin\Infrastructure\Repositories\PDOUserRepository;
use GripAndGrin\Application\UseCases\ChangePasswordUseCase;
use GripAndGrin\Application\UseCases\UpdateEmailUseCase;
use InvalidArgumentException;

class ProfileController
{
    private PDO $db;
    private PDOUserRepository $userRepository;
    private ChangePasswordUseCase $changePasswordUseCase;
    private UpdateEmailUseCase $updateEmailUseCase;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->userRepository = new PDOUserRepository($db);
        $this->changePasswordUseCase = new ChangePasswordUseCase($this->userRepository);
        $this->updateEmailUseCase = new UpdateEmailUseCase($this->userRepository);
    }

    public function show(): array
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Get user data
        $user = $this->userRepository->findById((int) $_SESSION['user_id']);

        if (!$user) {
            session_destroy();
            header('Location: /login');
            exit;
        }

        $data = [
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'fullName' => $user->getFullName(),
                'createdAt' => $user->getCreatedAt(),
                'isActive' => $user->isActive(),
                'isAdmin' => $user->isAdmin()
            ],
            'title' => 'Profile - ' . $user->getUsername()
        ];

        // Add flash messages if they exist
        if (isset($_SESSION['flash']['success'])) {
            $data['success'] = $_SESSION['flash']['success'];
            unset($_SESSION['flash']['success']);
        }

        if (isset($_SESSION['flash']['error'])) {
            $data['error'] = $_SESSION['flash']['error'];
            unset($_SESSION['flash']['error']);
        }

        return $data;
    }

    public function adminProfile(): array
    {
        // Check if user is admin/editor
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: /login');
            exit;
        }

        $user = $this->userRepository->findById((int) $_SESSION['user_id']);

        if (!$user || !$user->isEditor()) {
            header('Location: /login');
            exit;
        }

        return [
            'user' => $user,
            'title' => 'My Profile - ' . $user->getFullName()
        ];
    }

    public function changePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-profile');
            exit;
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: /login');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        try {
            if ($newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('New passwords do not match');
            }

            $this->changePasswordUseCase->execute(
                (int) $_SESSION['user_id'],
                $currentPassword,
                $newPassword
            );

            $_SESSION['flash']['success'] = 'Password changed successfully';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash']['error'] = $e->getMessage();
        }

        header('Location: /admin-profile');
        exit;
    }

    public function updateEmail(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-profile');
            exit;
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: /login');
            exit;
        }

        $newEmail = $_POST['email'] ?? '';

        try {
            $this->updateEmailUseCase->execute(
                (int) $_SESSION['user_id'],
                $newEmail
            );

            $_SESSION['flash']['success'] = 'Email address updated successfully';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash']['error'] = $e->getMessage();
        }

        header('Location: /admin-profile');
        exit;
    }
}
