<?php
/**
 * User.php
 * User model for database operations related to users
 * Handles authentication, profile management, and user queries
 */

declare(strict_types=1);

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get user by ID
     */
    public function getById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE id_user = ? LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get user by email
     */
    public function getByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create new user
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user (nom, prenom, email, password, role, date_inscription) 
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['role'] ?? 'artisan',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update user profile
     */
    public function update(int $userId, array $data): bool
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $userId;
        $stmt = $this->pdo->prepare('UPDATE user SET ' . implode(', ', $fields) . ' WHERE id_user = ?');
        return $stmt->execute($values);
    }

    /**
     * Verify user password
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return null;
        }

        if (password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM user WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result && (int)$result['count'] > 0;
    }

    /**
     * Reset password
     */
    public function resetPassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE user SET password = ? WHERE id_user = ?');
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Mark email as verified
     */
    public function markEmailVerified(int $userId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE user SET email_verified = 1 WHERE id_user = ?');
        return $stmt->execute([$userId]);
    }
}
