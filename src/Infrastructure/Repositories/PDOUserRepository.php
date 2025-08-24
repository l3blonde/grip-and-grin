<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use PDO;
use DateTime;

class PDOUserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function mapToEntity(array $data): User
    {
        return new User(
            $data['id'] ? (int) $data['id'] : null,
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['role'] ?? 'user',
            (bool) ($data['is_active'] ?? true),
            (bool) ($data['email_verified'] ?? false),
            $data['email_verification_token'] ?? null,
            $data['password_reset_token'] ?? null,
            isset($data['password_reset_expires']) && $data['password_reset_expires']
                ? new DateTime($data['password_reset_expires']) : null,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['bio'] ?? null,
            $data['avatar_path'] ?? null,
            isset($data['created_at']) && $data['created_at'] && $data['created_at'] !== '0000-00-00 00:00:00'
                ? new DateTime($data['created_at']) : new DateTime(),
            isset($data['updated_at']) && $data['updated_at'] && $data['updated_at'] !== '0000-00-00 00:00:00'
                ? new DateTime($data['updated_at']) : new DateTime()
        );
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW()
        ");
        $stmt->execute([$token]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function updatePassword(int $userId, string $newPasswordHash): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?
        ");

        return $stmt->execute([$newPasswordHash, $userId]);
    }

    public function setPasswordResetToken(int $userId, string $token, DateTime $expires): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET 
                password_reset_token = ?, 
                password_reset_expires = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");

        return $stmt->execute([$token, $expires->format('Y-m-d H:i:s'), $userId]);
    }

    public function clearPasswordResetToken(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET 
                password_reset_token = NULL, 
                password_reset_expires = NULL, 
                updated_at = NOW() 
            WHERE id = ?
        ");

        return $stmt->execute([$userId]);
    }

    public function updateEmail(int $userId, string $newEmail): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?
        ");

        return $stmt->execute([$newEmail, $userId]);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);

        return $stmt->fetchColumn() > 0;
    }

    public function save(User $user): ?User
    {
        if ($user->getId()) {
            // Update existing user
            $stmt = $this->pdo->prepare("
                UPDATE users SET 
                    username = ?, email = ?, password_hash = ?, role = ?, 
                    is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $user->getUsername(),
                $user->getEmail(),
                $user->getPasswordHash(),
                $user->getRole(),
                $user->isActive(),
                $user->getId()
            ]);
            return $user;
        } else {
            // Create new user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $user->getUsername(),
                $user->getEmail(),
                $user->getPasswordHash(),
                $user->getRole(),
                $user->isActive()
            ]);

            $userId = (int) $this->pdo->lastInsertId();
            return $this->findById($userId);
        }
    }

    public function deactivateUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function findAllByRole(string $role): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
        $stmt->execute([$role]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapToEntity'], $results);
    }

    public function getUserActivityLog(int $userId): array
    {
        // Basic activity log - in a real app this would be more comprehensive
        $stmt = $this->pdo->prepare("
            SELECT 'login' as action, updated_at as timestamp 
            FROM users WHERE id = ?
            ORDER BY updated_at DESC LIMIT 10
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
