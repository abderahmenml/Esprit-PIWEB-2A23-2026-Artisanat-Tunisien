<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/SupabaseSync.php';

/**
 * Modèle PasswordResetModel — Gestion des réinitialisations de mot de passe
 * Synchronise avec Supabase automatiquement
 */
class PasswordResetModel extends Model {
    private SupabaseSync $sync;

    public function __construct() {
        parent::__construct();
        $this->sync = new SupabaseSync();
    }

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
        $result = $stmt->execute([
            ':email'      => $email,
            ':code'       => $code,
            ':expires_at' => $expiresAt,
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->insert('password_resets', [
                'email'      => $email,
                'code'       => $code,
                'expires_at' => $expiresAt,
                'verified'   => false,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $result;
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
        $result = $stmt->execute([':email' => $email]);

        // Synchroniser avec Supabase
        if ($result) {
            // On ne peut pas faire un update basé sur l'email avec notre API simple
            // Donc on va juste loguer pour info
            error_log("Password reset marked as verified for: {$email}");
        }

        return $result;
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
