<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository
{
    protected Database $db;

    protected PDO $pdo;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->pdo = $db->pdo();
    }

    public function findById(int $id): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch();
    }

    public function findByEmail(string $email): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return $statement->fetch();
    }

    public function findByUsername(string $username): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);

        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, is_verified, avatar_url, locale) VALUES (:username, :email, :password_hash, :is_verified, :avatar_url, :locale)'
        );

        $statement->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'is_verified' => $data['is_verified'] ?? 0,
            'avatar_url' => $data['avatar_url'] ?? null,
            'locale' => $data['locale'] ?? 'zh',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $assignments = [];
        $params = ['id' => $id];

        foreach ($data as $column => $value) {
            $assignments[] = $column . ' = :' . $column;
            $params[$column] = $value;
        }

        $assignments[] = 'updated_at = CURRENT_TIMESTAMP';

        $statement = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $assignments) . ' WHERE id = :id');
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function verifyEmail(int $userId): bool
    {
        $statement = $this->pdo->prepare('UPDATE users SET is_verified = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute(['id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function updatePassword(int $userId, string $hash): bool
    {
        $statement = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute([
            'id' => $userId,
            'password_hash' => $hash,
        ]);

        return $statement->rowCount() > 0;
    }

    public function storeVerificationToken(int $userId, string $tokenHash, string $expiresAt): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO verification_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findVerificationToken(string $tokenHash): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT verification_tokens.*, users.email, users.username, users.is_verified
             FROM verification_tokens
             INNER JOIN users ON users.id = verification_tokens.user_id
             WHERE verification_tokens.token_hash = :token_hash
               AND verification_tokens.expires_at > :now
             ORDER BY verification_tokens.id DESC
             LIMIT 1'
        );
        $statement->execute([
            'token_hash' => $tokenHash,
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $statement->fetch();
    }

    public function deleteVerificationTokens(int $userId): int
    {
        $statement = $this->pdo->prepare('DELETE FROM verification_tokens WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }

    public function storeResetToken(int $userId, string $tokenHash, string $expiresAt): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findResetToken(string $tokenHash): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT password_reset_tokens.*, users.email, users.username, users.is_verified
             FROM password_reset_tokens
             INNER JOIN users ON users.id = password_reset_tokens.user_id
             WHERE password_reset_tokens.token_hash = :token_hash
               AND password_reset_tokens.expires_at > :now
             ORDER BY password_reset_tokens.id DESC
             LIMIT 1'
        );
        $statement->execute([
            'token_hash' => $tokenHash,
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $statement->fetch();
    }

    public function deleteResetTokens(int $userId): int
    {
        $statement = $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }
}
