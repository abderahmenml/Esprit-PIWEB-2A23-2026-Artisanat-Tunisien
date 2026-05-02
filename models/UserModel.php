<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/SupabaseSync.php';
require_once __DIR__ . '/../security/LoginSecurity.php';

/**
 * Modèle User — CRUD complet sur la table `user`
 * Hérite de Model qui fournit l'accès PDO
 * Synchronise avec Supabase automatiquement
 */
class UserModel extends Model {
    private SupabaseSync $sync;

    public function __construct() {
        parent::__construct();
        $this->sync = new SupabaseSync();
    }

    // ── CREATE ──────────────────────────────────────────────────────────────

    /**
     * Insère un nouvel utilisateur (depuis pending_users après vérification email)
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :date_creation, :etat_compte)";
        $stmt = $this->pdo->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $result = $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => $hashedPassword,
            ':role'         => $data['role'],
            ':date_creation'=> date('Y-m-d'),
            ':etat_compte'  => $data['etat_compte'] ?? 'actif',
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $userId = $this->pdo->lastInsertId();
            $this->sync->insert('user', [
                'id_user'       => (int)$userId,
                'nom'           => $data['nom'],
                'prenom'        => $data['prenom'],
                'email'         => $data['email'],
                'mot_de_passe'  => $hashedPassword,
                'role'          => $data['role'],
                'date_creation' => date('Y-m-d'),
                'etat_compte'   => $data['etat_compte'] ?? 'actif',
            ]);
        }

        return $result;
    }

    /**
     * Crée directement un utilisateur (par admin, sans vérification email)
     */
    public function createDirect(array $data): bool {
        $sql = "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :date_creation, :etat_compte)";
        $stmt = $this->pdo->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $result = $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => $hashedPassword,
            ':role'         => $data['role'],
            ':date_creation'=> date('Y-m-d'),
            ':etat_compte'  => $data['etat_compte'] ?? 'actif',
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $userId = $this->pdo->lastInsertId();
            $this->sync->insert('user', [
                'id_user'       => (int)$userId,
                'nom'           => $data['nom'],
                'prenom'        => $data['prenom'],
                'email'         => $data['email'],
                'mot_de_passe'  => $hashedPassword,
                'role'          => $data['role'],
                'date_creation' => date('Y-m-d'),
                'etat_compte'   => $data['etat_compte'] ?? 'actif',
            ]);
        }

        return $result;
    }

    /**
     * Crée un utilisateur vérifié (depuis email verification)
     * Utilisé quand l'email est confirmé et le hash est déjà existant
     */
    public function createVerified(array $data): bool {
        $sql = "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :date_creation, :etat_compte)";
        $stmt = $this->pdo->prepare($sql);
        
        // Le hash est déjà fourni (pas de re-hash)
        $result = $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':mot_de_passe' => $data['mot_de_passe'],  // Hash déjà existant
            ':role'         => $data['role'],
            ':date_creation'=> date('Y-m-d'),
            ':etat_compte'  => 'actif',
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $userId = $this->pdo->lastInsertId();
            $this->sync->insert('user', [
                'id_user'       => (int)$userId,
                'nom'           => $data['nom'],
                'prenom'        => $data['prenom'],
                'email'         => $data['email'],
                'mot_de_passe'  => $data['mot_de_passe'],
                'role'          => $data['role'],
                'date_creation' => date('Y-m-d'),
                'etat_compte'   => 'actif',
            ]);
        }

        return $result;
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

    public function findByFaceId(string $faceId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE face_id = :face_id LIMIT 1");
        $stmt->execute([':face_id' => $faceId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAllWithFaceDescriptor(): array {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM user
             WHERE face_descriptor IS NOT NULL
               AND TRIM(face_descriptor) != ''"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateFaceDescriptor(int $id, string $faceDescriptor): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user SET face_descriptor = :face_descriptor WHERE id_user = :id"
        );
        $result = $stmt->execute([
            ':face_descriptor' => $faceDescriptor,
            ':id' => $id,
        ]);

        if ($result) {
            $this->sync->update('user', $id, [
                'face_descriptor' => $faceDescriptor,
            ], 'id_user');
        }

        return $result;
    }

    public function updateFaceData(int $id, string $faceDescriptor, string $faceId): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user
             SET face_descriptor = :face_descriptor,
                 face_id = :face_id
             WHERE id_user = :id"
        );
        $result = $stmt->execute([
            ':face_descriptor' => $faceDescriptor,
            ':face_id' => $faceId,
            ':id' => $id,
        ]);

        if ($result) {
            $this->sync->update('user', $id, [
                'face_descriptor' => $faceDescriptor,
                'face_id' => $faceId,
            ], 'id_user');
        }

        return $result;
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
        $result = $stmt->execute([
            ':nom'         => $data['nom'],
            ':prenom'      => $data['prenom'],
            ':email'       => $data['email'],
            ':role'        => $data['role'],
            ':etat_compte' => $data['etat_compte'],
            ':id'          => $id,
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->update('user', $id, [
                'nom'         => $data['nom'],
                'prenom'      => $data['prenom'],
                'email'       => $data['email'],
                'role'        => $data['role'],
                'etat_compte' => $data['etat_compte'],
            ], 'id_user');
        }

        return $result;
    }

    /**
     * Met à jour le mot de passe d'un utilisateur
     */
    public function updatePassword(int $id, string $newPassword): bool {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "UPDATE user SET mot_de_passe = :mdp WHERE id_user = :id"
        );
        $result = $stmt->execute([
            ':mdp' => $hashedPassword,
            ':id'  => $id,
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->update('user', $id, [
                'mot_de_passe' => $hashedPassword,
            ], 'id_user');
        }

        return $result;
    }

    /**
     * Met à jour l'état du compte (actif / inactif / suspendu)
     */
    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user SET etat_compte = :status WHERE id_user = :id"
        );
        $result = $stmt->execute([':status' => $status, ':id' => $id]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->update('user', $id, [
                'etat_compte' => $status,
            ], 'id_user');
        }

        return $result;
    }

    /**
     * Met à jour le mot de passe via email (reset)
     */
    public function updatePasswordByEmail(string $email, string $newPassword): bool {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "UPDATE user SET mot_de_passe = :mdp WHERE email = :email"
        );
        $result = $stmt->execute([
            ':mdp'   => $hashedPassword,
            ':email' => $email,
        ]);

        // Récupérer l'ID utilisateur et synchroniser avec Supabase
        if ($result) {
            $userStmt = $this->pdo->prepare("SELECT id_user FROM user WHERE email = :email LIMIT 1");
            $userStmt->execute([':email' => $email]);
            $user = $userStmt->fetch();
            if ($user) {
                $this->sync->update('user', (int)$user['id_user'], [
                    'mot_de_passe' => $hashedPassword,
                ], 'id_user');
            }
        }

        return $result;
    }

    // ── DELETE ───────────────────────────────────────────────────────────────

    /**
     * Met à jour le profil de l'utilisateur (date_naissance, statut_marital)
     */
    public function updateProfile(int $id, array $data): bool {
        $sql = "UPDATE user SET";
        $updates = [];
        $params = [':id' => $id];

        if (isset($data['date_naissance'])) {
            $updates[] = "date_naissance = :date_naissance";
            $params[':date_naissance'] = $data['date_naissance'] ?: null;
        }
        if (isset($data['statut_marital'])) {
            $updates[] = "statut_marital = :statut_marital";
            $params[':statut_marital'] = $data['statut_marital'] ?: null;
        }
        if (isset($data['image_profil'])) {
            $updates[] = "image_profil = :image_profil";
            $params[':image_profil'] = $data['image_profil'] ?: null;
        }
        if (isset($data['nom'])) {
            $updates[] = "nom = :nom";
            $params[':nom'] = $data['nom'];
        }
        if (isset($data['prenom'])) {
            $updates[] = "prenom = :prenom";
            $params[':prenom'] = $data['prenom'];
        }

        if (empty($updates)) {
            return false;
        }

        $sql .= " " . implode(", ", $updates) . " WHERE id_user = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);

        // Synchroniser avec Supabase
        if ($result) {
            $syncData = [];
            if (isset($data['date_naissance'])) $syncData['date_naissance'] = $data['date_naissance'];
            if (isset($data['statut_marital'])) $syncData['statut_marital'] = $data['statut_marital'];
            if (isset($data['image_profil'])) $syncData['image_profil'] = $data['image_profil'];
            if (isset($data['nom'])) $syncData['nom'] = $data['nom'];
            if (isset($data['prenom'])) $syncData['prenom'] = $data['prenom'];
            if (!empty($syncData)) {
                $this->sync->update('user', $id, $syncData, 'id_user');
            }
        }

        return $result;
    }

    /**
     * Change l'état de blocage d'un compte (par l'admin)
     */
    public function toggleBlocked(int $id, bool $blocked): bool {
        if ($blocked) {
            $stmt = $this->pdo->prepare(
                "UPDATE user
                 SET is_blocked = 1
                 WHERE id_user = :id"
            );
            $result = $stmt->execute([':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE user
                 SET is_blocked = 0,
                     failed_attempts = 0,
                     blocked_until = NULL,
                     permanently_locked = 0
                 WHERE id_user = :id"
            );
            $result = $stmt->execute([':id' => $id]);
        }

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->update('user', $id, [
                'is_blocked' => $blocked ? 1 : 0,
            ], 'id_user');
        }

        return $result;
    }

    /**
     * Vérifie si un compte est bloqué
     */
    public function isBlocked(int $id): bool {
        $stmt = $this->pdo->prepare("SELECT is_blocked FROM user WHERE id_user = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result && $result['is_blocked'] ? true : false;
    }

    public function resetLoginSecurity(int $id): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE user
             SET failed_attempts = 0,
                 blocked_until = NULL,
                 permanently_locked = 0
             WHERE id_user = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function registerFailedPasswordAttempt(int $id, LoginSecurity $security): array {
        $user = $this->findById($id);
        if (!$user) {
            return ['success' => false, 'failed_attempts' => 0, 'temporary_seconds' => 0, 'permanent' => false];
        }

        $failedAttempts = ((int) ($user['failed_attempts'] ?? 0)) + 1;
        $temporarySeconds = $security->getTemporaryLockDuration($failedAttempts);
        $permanent = $security->isPermanentLock($failedAttempts);
        $blockedUntil = $temporarySeconds > 0 ? date('Y-m-d H:i:s', time() + $temporarySeconds) : null;

        $stmt = $this->pdo->prepare(
            "UPDATE user
             SET failed_attempts = :failed_attempts,
                 blocked_until = :blocked_until,
                 permanently_locked = :permanent
             WHERE id_user = :id"
        );

        $ok = $stmt->execute([
            ':failed_attempts' => $failedAttempts,
            ':blocked_until' => $blockedUntil,
            ':permanent' => $permanent ? 1 : 0,
            ':id' => $id,
        ]);

        return [
            'success' => $ok,
            'failed_attempts' => $failedAttempts,
            'temporary_seconds' => $temporarySeconds,
            'permanent' => $permanent,
        ];
    }

    public function getLoginLockState(array $user): array {
        $permanent = !empty($user['permanently_locked']);
        $blockedUntil = $user['blocked_until'] ?? null;

        if ($permanent) {
            return [
                'locked' => true,
                'permanent' => true,
                'remaining_seconds' => 0,
            ];
        }

        if (!empty($blockedUntil)) {
            $remaining = strtotime($blockedUntil) - time();
            if ($remaining > 0) {
                return [
                    'locked' => true,
                    'permanent' => false,
                    'remaining_seconds' => $remaining,
                ];
            }
        }

        return [
            'locked' => false,
            'permanent' => false,
            'remaining_seconds' => 0,
        ];
    }

    /**
     * Supprime un utilisateur par son ID
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM user WHERE id_user = :id");
        $result = $stmt->execute([':id' => $id]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->delete('user', $id, 'id_user');
        }

        return $result;
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
