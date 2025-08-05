<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use DateTime;
use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\UserRole;
use GripAndGrin\Infrastructure\Database\DatabaseConnection;
use PDO;

class PDOUserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct(DatabaseConnection $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToUser($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToUser($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToUser($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");

        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = $this->mapRowToUser($row);
        }
        return $users;
    }

    public function save(User $user): User
    {
        if ($user->getId() === 0) {
            return $this->insert($user);
        }
        return $this->update($user);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    private function insert(User $user): User
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, role, is_active, email_verified, first_name, last_name, bio, avatar_path, created_at) 
            VALUES (:username, :email, :password_hash, :role, :is_active, :email_verified, :first_name, :last_name, :bio, :avatar_path, :created_at)
        ");

        $stmt->execute([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'role' => $user->getRole()->getValue(),
            'is_active' => $user->isActive() ? 1 : 0,
            'email_verified' => $user->isEmailVerified() ? 1 : 0,
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'bio' => $user->getBio(),
            'avatar_path' => $user->getAvatarPath(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = (int) $this->db->lastInsertId();

        return new User(
            $id,
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getRole(),
            $user->getCreatedAt(),
            $user->isActive(),
            $user->isEmailVerified(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getBio(),
            $user->getAvatarPath()
        );
    }

    private function update(User $user): User
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET username = :username, email = :email, password_hash = :password_hash, role = :role, 
                is_active = :is_active, email_verified = :email_verified, first_name = :first_name, 
                last_name = :last_name, bio = :bio, avatar_path = :avatar_path
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'role' => $user->getRole()->getValue(),
            'is_active' => $user->isActive() ? 1 : 0,
            'email_verified' => $user->isEmailVerified() ? 1 : 0,
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'bio' => $user->getBio(),
            'avatar_path' => $user->getAvatarPath()
        ]);

        return $user;
    }

    private function mapRowToUser(array $row): User
    {
        return new User(
            (int)$row['id'],
            $row['username'],
            $row['email'],
            $row['password_hash'],
            new UserRole($row['role']),
            new DateTime($row['created_at']),
            (bool)$row['is_active'],
            (bool)$row['email_verified'],
            $row['first_name'],
            $row['last_name'],
            $row['bio'],
            $row['avatar_path']
        );
    }
}
