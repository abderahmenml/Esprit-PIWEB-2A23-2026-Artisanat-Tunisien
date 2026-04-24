<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Modèle PendingUserModel — Gestion des inscriptions en attente de vérification email
 */
class PendingUserModel extends Model {

    /**
     * Insère ou met à jour un utilisateur en attente
     */
    public function upsert(array $data): bool {
        $sql = "INSERT INTO pending_users (nom, prenom, email, mot_de_passe, role, token, expires_at)
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :token, :expires_at)
                ON DUPLICATE KEY UPDATE
                    nom          = VALUES(nom),
                    prenom       = VALUES(prenom),
                    mot_de_passe = VALUES(mot_de_passe),
                    role         = VALUES(role),
                    token        = VALUES(token),
                    expires_at   = VALUES(expires_at)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => $data['mot_de_passe'],
            ':role'         => $data['role'],
            ':token'        => $data['token'],
            ':expires_at'   => $data['expires_at'],
        ]);
    }

    /**
     * Trouve un utilisateur en attente par token
     */
    public function findByToken(string $token): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pending_users WHERE token = :token LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Supprime un utilisateur en attente par ID
     */
    public function deleteById(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM pending_users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
