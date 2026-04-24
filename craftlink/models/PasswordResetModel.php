<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Modèle PasswordResetModel — Gestion des réinitialisations de mot de passe
 */
class PasswordResetModel extends Model {

    /**
     * Insère ou met à jour un code de réinitialisation
     */
    public function upsert(string $email, string $code, string $expiresAt): bool {
        $sql = "INSERT INTO password_resets (email, code, expires_at, verified)
                VALUES (:email, :code, :expires_at, 0)
                ON DUPLICATE KEY UPDATE
                    code       = VALUES(code),
                    expires_at = VALUES(expires_at),
                    verified   = 0,
                    created_at = CURRENT_TIMESTAMP";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':email'      => $email,
            ':code'       => $code,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * Trouve une demande de reset par email
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM password_resets WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Marque le code comme vérifié
     */
    public function markVerified(string $email): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE password_resets SET verified = 1 WHERE email = :email"
        );
        return $stmt->execute([':email' => $email]);
    }

    /**
     * Supprime la demande de reset (après succès)
     */
    public function deleteByEmail(string $email): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM password_resets WHERE email = :email"
        );
        return $stmt->execute([':email' => $email]);
    }
}
