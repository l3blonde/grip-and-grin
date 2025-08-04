<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Repositories;

use DateTime;
use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
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
            INSERT INTO users (username, email, password_hash, created_at, is_active) 
            VALUES (:username, :email, :password_hash, :created_at, :is_active)
        ");

        $stmt->execute([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'is_active' => $user->isActive() ? 1 : 0
        ]);

        $id = (int) $this->db->lastInsertId();

        return new User(
            $id,
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getCreatedAt(),
            $user->isActive()
        );
    }

    private function update(User $user): User
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET username = :username, email = :email, password_hash = :password_hash, is_active = :is_active
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'is_active' => $user->isActive() ? 1 : 0
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
            new DateTime($row['created_at']),
            (bool)$row['is_active']
        );
    }
}
