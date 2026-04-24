<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Modèle User — CRUD complet sur la table `user`
 * Hérite de Model qui fournit l'accès PDO
 */
class UserModel extends Model {

    // ── CREATE ──────────────────────────────────────────────────────────────

    /**
     * Insère un nouvel utilisateur (depuis pending_users après vérification email)
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :date_creation, :etat_compte)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'         => $data['role'],
            ':date_creation'=> date('Y-m-d'),
            ':etat_compte'  => $data['etat_compte'] ?? 'actif',
        ]);
    }

    /**
     * Crée directement un utilisateur (par admin, sans vérification email)
     */
    public function createDirect(array $data): bool {
        $sql = "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :date_creation, :etat_compte)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'         => $data['role'],
            ':date_creation'=> date('Y-m-d'),
            ':etat_compte'  => $data['etat_compte'] ?? 'actif',
        ]);
    }

    // ── READ ─────────────────────────────────────────────────────────────────

    /**
     * Récupère tous les utilisateurs (avec filtres optionnels)
     */
    public function findAll(array $filters = [], string $orderBy = 'date_creation DESC'): array {
        $sql = "SELECT * FROM user";
        $params = [];
        $conditions = [];

        if (!empty($filters['role'])) {
            $conditions[] = "role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['etat_compte'])) {
            $conditions[] = "etat_compte = :etat_compte";
            $params[':etat_compte'] = $filters['etat_compte'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY $orderBy";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un utilisateur par son ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE id_user = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère un utilisateur par son email
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Compte les utilisateurs (avec filtres optionnels)
     */
    public function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM user";
        $params = [];
        $conditions = [];

        if (!empty($filters['role'])) {
            $conditions[] = "role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['etat_compte'])) {
            $conditions[] = "etat_compte = :etat_compte";
            $params[':etat_compte'] = $filters['etat_compte'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Statistiques par rôle
     */
    public function countByRole(): array {
        $stmt = $this->pdo->query(
            "SELECT role, COUNT(*) as total FROM user GROUP BY role ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }

    // ── UPDATE ───────────────────────────────────────────────────────────────

    /**
     * Met à jour les informations d'un utilisateur
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE user SET
                    nom          = :nom,
                    prenom       = :prenom,
                    email        = :email,
                    role         = :role,
                    etat_compte  = :etat_compte
                WHERE id_user = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nom'         => $data['nom'],
            ':prenom'      => $data['prenom'],
            ':email'       => $data['email'],
            ':role'        => $data['role'],
            ':etat_compte' => $data['etat_compte'],
            ':id'          => $id,
        ]);
    }

    /**
     * Met à jour le mot de passe d'un utilisateur
     */
    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user SET mot_de_passe = :mdp WHERE id_user = :id"
        );
        return $stmt->execute([
            ':mdp' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id'  => $id,
        ]);
    }

    /**
     * Met à jour l'état du compte (actif / inactif / suspendu)
     */
    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user SET etat_compte = :status WHERE id_user = :id"
        );
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Met à jour le mot de passe via email (reset)
     */
    public function updatePasswordByEmail(string $email, string $newPassword): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user SET mot_de_passe = :mdp WHERE email = :email"
        );
        return $stmt->execute([
            ':mdp'   => password_hash($newPassword, PASSWORD_DEFAULT),
            ':email' => $email,
        ]);
    }

    // ── DELETE ───────────────────────────────────────────────────────────────

    /**
     * Supprime un utilisateur par son ID
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM user WHERE id_user = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ── UTILITAIRES ──────────────────────────────────────────────────────────

    /**
     * Vérifie si un email est déjà utilisé (optionnellement en excluant un ID)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool {
        if ($excludeId) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM user WHERE email = :email AND id_user != :id"
            );
            $stmt->execute([':email' => $email, ':id' => $excludeId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM user WHERE email = :email"
            );
            $stmt->execute([':email' => $email]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie le mot de passe d'un utilisateur
     */
    public function verifyPassword(string $inputPassword, string $hashedPassword): bool {
        return password_verify($inputPassword, $hashedPassword);
    }
}
