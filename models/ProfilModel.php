<?php
// models/ProfilModel.php

require_once __DIR__ . '/../config/config.php';

class ProfilModel
{
    private PDO $pdo;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // =========================================================
    // USER
    // =========================================================

    /**
     * Retourne toutes les colonnes user + profil_professionnel pour un user.
     * Les colonnes profil_professionnel sont préfixées "profil_" pour éviter
     * les collisions (ex: profil_telephone, profil_bio...).
     */
    public function getUserById(int $id): array|false
    {
        $profileSelect = implode(",\n                ", [
            $this->profileSelectExpr('specialite'),
            $this->profileSelectExpr('bio', 'profil_bio'),
            $this->profileSelectExpr('portfolio'),
            $this->profileSelectExpr('experience'),
            $this->profileSelectExpr('ville'),
            $this->profileSelectExpr('telephone', 'profil_telephone'),
            $this->profileSelectExpr('disponibilite'),
            $this->profileSelectExpr('disponibilite_horaire'),
            $this->profileSelectExpr('disponibilite_message'),
            $this->profileSelectExpr('disponibilite_slots'),
            $this->profileSelectExpr('disponibilite_exceptions'),
            $this->profileSelectExpr('disponibilite_conges'),
            $this->profileSelectExpr('date_creation'),
        ]);

        $metricsSelect = $this->hasTable('profil_metrics')
            ? "pm.profile_score, pm.popularity_score, pm.insight_last_calc_at"
            : "NULL AS profile_score, NULL AS popularity_score, NULL AS insight_last_calc_at";

        $profileJoin = $this->hasTable('profil_professionnel')
            ? "LEFT JOIN profil_professionnel pp ON pp.id_user = u.id_user"
            : "";

        $metricsJoin = $this->hasTable('profil_metrics')
            ? "LEFT JOIN profil_metrics pm ON pm.id_user = u.id_user"
            : "";

        $stmt = $this->pdo->prepare("
            SELECT
                u.*,
                $profileSelect,
                $metricsSelect
            FROM user u
            $profileJoin
            $metricsJoin
            WHERE u.id_user = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================================================
    // BIO
    // =========================================================

    /**
     * Retourne la bio de l'utilisateur (stockée dans profil_professionnel.bio).
     */
    public function getBioByUserId(int $userId): string
    {
        if (!$this->hasTable('profil_professionnel') || !$this->hasColumn('profil_professionnel', 'bio')) {
            return '';
        }

        $stmt = $this->pdo->prepare(
            "SELECT bio FROM profil_professionnel WHERE id_user = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        return (string)($stmt->fetchColumn() ?: '');
    }

    /**
     * Ajoute une bio (INSERT) — uniquement si la ligne profil_professionnel existe déjà.
     * Retourne true si une ligne a été affectée.
     */
    public function addBioForUser(int $userId, string $bio): bool
    {
        if (!$this->hasTable('profil_professionnel') || !$this->hasColumn('profil_professionnel', 'bio')) {
            return false;
        }

        // Si la ligne profil_professionnel n'existe pas encore, on la crée
        $this->ensureProfilProfessionnel($userId);

        $stmt = $this->pdo->prepare("
            UPDATE profil_professionnel
            SET bio = ?
            WHERE id_user = ? AND (bio IS NULL OR bio = '')
        ");
        $stmt->execute([$bio, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Met à jour une bio existante.
     */
    public function updateBioForUser(int $userId, string $bio): bool
    {
        if (!$this->hasTable('profil_professionnel') || !$this->hasColumn('profil_professionnel', 'bio')) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE profil_professionnel SET bio = ? WHERE id_user = ?
        ");
        $stmt->execute([$bio, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime la bio (met à NULL).
     */
    public function deleteBioForUser(int $userId): bool
    {
        if (!$this->hasTable('profil_professionnel') || !$this->hasColumn('profil_professionnel', 'bio')) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE profil_professionnel SET bio = NULL WHERE id_user = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================
    // PROFIL UPDATE
    // =========================================================

    public function updateProfil(int $userId, array $data): void
    {
        // 1. Mettre à jour la table user
        $stmt = $this->pdo->prepare("
            UPDATE user
            SET nom = ?, prenom = ?, email = ?, telephone = ?
            WHERE id_user = ?
        ");
        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['telephone'],
            $userId,
        ]);

        // 2. Upsert profil_professionnel
        $this->ensureProfilProfessionnel($userId);

        if (!$this->hasTable('profil_professionnel')) {
            return;
        }

        $updatable = [
            'specialite' => 'specialite',
            'ville' => 'ville',
            'disponibilite' => 'disponibilite',
            'disponibilite_horaire' => 'disponibilite_horaire',
            'disponibilite_message' => 'disponibilite_message',
            'disponibilite_slots' => 'disponibilite_slots',
            'disponibilite_exceptions' => 'disponibilite_exceptions',
            'disponibilite_conges' => 'disponibilite_conges',
        ];

        $setParts = [];
        $params = [];
        foreach ($updatable as $column => $dataKey) {
            if ($this->hasColumn('profil_professionnel', $column)) {
                $setParts[] = "$column = ?";
                $params[] = $data[$dataKey] ?? null;
            }
        }

        if (empty($setParts)) {
            return;
        }

        $params[] = $userId;
        $stmt = $this->pdo->prepare(
            "UPDATE profil_professionnel SET " . implode(', ', $setParts) . " WHERE id_user = ?"
        );
        $stmt->execute($params);
    }

    /**
     * Crée la ligne profil_professionnel si elle n'existe pas encore.
     */
    private function ensureProfilProfessionnel(int $userId): void
    {
        if (!$this->hasTable('profil_professionnel')) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM profil_professionnel WHERE id_user = ?"
        );
        $stmt->execute([$userId]);

        if ((int)$stmt->fetchColumn() === 0) {
            $columns = ['id_user'];
            $values = ['?'];
            $params = [$userId];

            if ($this->hasColumn('profil_professionnel', 'date_creation')) {
                $columns[] = 'date_creation';
                $values[] = 'CURDATE()';
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO profil_professionnel (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')'
            );
            $stmt->execute($params);
        }
    }

    // =========================================================
    // STATS
    // =========================================================

    public function getStats(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM projet WHERE id_user = ?"
            );
            $stmt->execute([$userId]);
            $projets = (int)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare(
                "SELECT AVG(note) FROM avis WHERE id_user_recepteur = ?"
            );
            $stmt->execute([$userId]);
            $note = number_format((float)($stmt->fetchColumn() ?: 0), 1);

            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM application_offre WHERE id_mentor = ? AND statut = 'accepte'"
            );
            $stmt->execute([$userId]);
            $mentores = (int)$stmt->fetchColumn();

            return compact('projets', 'note', 'mentores');
        } catch (Exception) {
            return ['projets' => 0, 'note' => '0.0', 'mentores' => 0];
        }
    }

    // =========================================================
    // COMPÉTENCES
    // =========================================================

    public function getCompetences(int $userId): array
    {
        if (!$this->hasTable('competences')) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT id_competence, nom_competence, description, niveau, ordre
            FROM competences
            WHERE id_user = ?
            ORDER BY ordre ASC, id_competence ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addCompetence(int $userId, string $nom, string $desc, int $niveau): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(MAX(ordre), 0) + 1 FROM competences WHERE id_user = ?"
        );
        $stmt->execute([$userId]);
        $ordre = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            INSERT INTO competences (id_user, nom_competence, description, niveau, ordre)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $nom, $desc, $niveau, $ordre]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteCompetence(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM competences WHERE id_competence = ? AND id_user = ?"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateCompetence(int $id, int $userId, string $nom, string $desc, int $niveau): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE competences
            SET nom_competence = ?, description = ?, niveau = ?
            WHERE id_competence = ? AND id_user = ?
        ");
        $stmt->execute([$nom, $desc, $niveau, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================
    // CERTIFICATIONS
    // =========================================================

    public function getCertifications(int $userId): array
    {
        if (!$this->hasTable('certification')) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT id_certification, nom_certification, niveau, ordre
            FROM certification
            WHERE id_user = ?
            ORDER BY ordre ASC, id_certification ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addCertification(int $userId, string $nom, int $niveau): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(MAX(ordre), 0) + 1 FROM certification WHERE id_user = ?"
        );
        $stmt->execute([$userId]);
        $ordre = (int)$stmt->fetchColumn() ?: 1;

        $stmt = $this->pdo->prepare("
            INSERT INTO certification (id_user, nom_certification, niveau, ordre)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $nom, $niveau, $ordre]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteCertification(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM certification WHERE id_certification = ? AND id_user = ?"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateCertification(int $id, int $userId, string $nom, int $niveau): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE certification
            SET nom_certification = ?, niveau = ?
            WHERE id_certification = ? AND id_user = ?
        ");
        $stmt->execute([$nom, $niveau, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================
    // EXPÉRIENCES
    // =========================================================

    public function getExperiences(int $userId): array
    {
        if (!$this->hasTable('experience')) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT id_experience, poste, entreprise, date_debut, date_fin, description
            FROM experience
            WHERE id_user = ?
            ORDER BY date_debut DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addExperience(
        int    $userId,
        string $poste,
        string $entreprise,
        string $dateDebut,
        string $dateFin,
        string $description
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO experience
                (id_user, poste, entreprise, date_debut, date_fin, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $poste,
            $entreprise,
            $dateDebut,
            $dateFin !== '' ? $dateFin : null,
            $description,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteExperience(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM experience WHERE id_experience = ? AND id_user = ?"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateExperience(
        int    $id,
        int    $userId,
        string $poste,
        string $entreprise,
        string $dateDebut,
        string $dateFin,
        string $description
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE experience
            SET poste = ?, entreprise = ?, date_debut = ?, date_fin = ?, description = ?
            WHERE id_experience = ? AND id_user = ?
        ");
        $stmt->execute([
            $poste,
            $entreprise,
            $dateDebut,
            $dateFin !== '' ? $dateFin : null,
            $description,
            $id,
            $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================
    // PORTFOLIO FILES
    // =========================================================

    public function getPortfolioFiles(int $userId): array
    {
        if (!$this->hasTable('portfolio_files')) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT id_portfolio_file, titre, realisation, file_name, file_path, created_at
            FROM portfolio_files
            WHERE id_user = ?
            ORDER BY created_at DESC, id_portfolio_file DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // =========================================================
    // AVIS
    // =========================================================

    public function getAvis(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.prenom, u.nom
                FROM avis a
                JOIN user u ON a.id_user_auteur = u.id_user
                WHERE a.id_user_recepteur = ?
                ORDER BY a.date_avis DESC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception) {
            return [];
        }
    }

    // =========================================================
    // INSIGHT / METRICS CACHE
    // =========================================================

    /**
     * Sauvegarde les métriques calculées dans profil_metrics (upsert).
     */
    public function saveInsightMetrics(int $userId, array $metrics): void
    {
        if (!$this->hasTable('profil_metrics')) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO profil_metrics
                    (id_user, profile_score, popularity_score, suggested_jobs, is_trending, insight_last_calc_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    profile_score        = VALUES(profile_score),
                    popularity_score     = VALUES(popularity_score),
                    suggested_jobs       = VALUES(suggested_jobs),
                    is_trending          = VALUES(is_trending),
                    insight_last_calc_at = NOW()
            ");
            $stmt->execute([
                $userId,
                (int)($metrics['profile_score']    ?? 0),
                (int)($metrics['popularity_score'] ?? 0),
                (int)($metrics['suggested_jobs']   ?? 0),
                (int)(!empty($metrics['is_trending'])),
            ]);
        } catch (Exception) {
            // Avoid breaking profile rendering when metrics cache table is absent or invalid.
        }
    }

    // =========================================================
    // MÉTIERS AVANCÉS
    // =========================================================

    /**
     * Retourne les métiers avancés (max 2) pour un utilisateur.
     * Les données IA sont décodées automatiquement.
     */
    public function getMetiersAvances(int $userId): array
    {
        if (!$this->hasTable('metiers_avances')) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                id_metier,
                id_user,
                slot,
                titre,
                description,
                niveau_maitrise,
                technologies,
                ia_recommandations,
                ia_projection_salaire,
                ia_tendance_marche,
                ia_score_adequation,
                ia_updated_at,
                created_at,
                updated_at
            FROM metiers_avances
            WHERE id_user = ?
            ORDER BY slot ASC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Décoder les champs JSON
        foreach ($rows as &$row) {
            $row['technologies']       = $this->decodeJsonColumn($row['technologies']);
            $row['ia_recommandations'] = $this->decodeJsonColumn($row['ia_recommandations']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Retourne un métier avancé par son ID (avec vérification propriétaire).
     */
    public function getMetierById(int $idMetier, int $userId): array|false
    {
        if (!$this->hasTable('metiers_avances')) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM metiers_avances
            WHERE id_metier = ? AND id_user = ?
            LIMIT 1
        ");
        $stmt->execute([$idMetier, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        $row['technologies']       = $this->decodeJsonColumn($row['technologies']);
        $row['ia_recommandations'] = $this->decodeJsonColumn($row['ia_recommandations']);
        return $row;
    }

    /**
     * Crée ou met à jour un métier avancé (slot 1 ou 2).
     * Retourne l'ID du métier créé ou mis à jour.
     */
    public function upsertMetierAvance(
        int    $userId,
        int    $slot,
        string $titre,
        string $description,
        int    $niveauMaitrise,
        array  $technologies
    ): int {
        $techJson = json_encode($technologies, JSON_UNESCAPED_UNICODE);

        // Vérifie si le slot existe déjà pour cet utilisateur
        $stmt = $this->pdo->prepare(
            "SELECT id_metier FROM metiers_avances WHERE id_user = ? AND slot = ? LIMIT 1"
        );
        $stmt->execute([$userId, $slot]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            // UPDATE
            $stmt = $this->pdo->prepare("
                UPDATE metiers_avances
                SET titre          = ?,
                    description    = ?,
                    niveau_maitrise= ?,
                    technologies   = ?,
                    updated_at     = NOW()
                WHERE id_metier = ? AND id_user = ?
            ");
            $stmt->execute([$titre, $description, $niveauMaitrise, $techJson, $existing, $userId]);
            return (int)$existing;
        }

        // INSERT
        $stmt = $this->pdo->prepare("
            INSERT INTO metiers_avances
                (id_user, slot, titre, description, niveau_maitrise, technologies, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $slot, $titre, $description, $niveauMaitrise, $techJson]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Supprime un métier avancé (avec vérification propriétaire).
     */
    public function deleteMetierAvance(int $idMetier, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM metiers_avances WHERE id_metier = ? AND id_user = ?"
        );
        $stmt->execute([$idMetier, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Sauvegarde les données IA dans la ligne du métier avancé.
     *
     * @param int    $idMetier
     * @param int    $userId         Pour vérifier le propriétaire
     * @param array  $recommandations
     * @param string $projectionSalaire
     * @param string $tendanceMarche
     * @param int    $scoreAdequation
     */
    public function saveMetierIaData(
        int    $idMetier,
        int    $userId,
        array  $recommandations,
        string $projectionSalaire,
        string $tendanceMarche,
        int    $scoreAdequation
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE metiers_avances
            SET ia_recommandations    = ?,
                ia_projection_salaire = ?,
                ia_tendance_marche    = ?,
                ia_score_adequation   = ?,
                ia_updated_at         = NOW()
            WHERE id_metier = ? AND id_user = ?
        ");
        $stmt->execute([
            json_encode($recommandations, JSON_UNESCAPED_UNICODE),
            $projectionSalaire,
            $tendanceMarche,
            $scoreAdequation,
            $idMetier,
            $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================
    // HELPERS PRIVÉS
    // =========================================================

    private function decodeJsonColumn(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function profileSelectExpr(string $column, ?string $alias = null): string
    {
        $alias = $alias ?? $column;

        if ($this->hasTable('profil_professionnel') && $this->hasColumn('profil_professionnel', $column)) {
            if ($alias !== $column) {
                return "pp.`$column` AS `$alias`";
            }
            return "pp.`$column`";
        }

        return "NULL AS `$alias`";
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            $exists = $stmt->fetchColumn() !== false;
        } catch (Exception) {
            $exists = false;
        }

        $this->tableExistsCache[$table] = $exists;
        return $exists;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }

        if (!$this->hasTable($table)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);
            $exists = (int)$stmt->fetchColumn() > 0;
        } catch (Exception) {
            $exists = false;
        }

        $this->columnExistsCache[$key] = $exists;
        return $exists;
    }
}