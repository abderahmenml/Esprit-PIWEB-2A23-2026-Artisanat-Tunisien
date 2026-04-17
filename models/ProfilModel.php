<?php
require_once __DIR__ . '/../config/config.php';

class ProfilModel
{
    private PDO $pdo;
    private array $tableCache = [];
    private array $columnCache = [];

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        $exists = (int)$stmt->fetchColumn() > 0;

        $this->tableCache[$table] = $exists;
        return $exists;
    }

    private function getColumns(string $table): array
    {
        if (array_key_exists($table, $this->columnCache)) {
            return $this->columnCache[$table];
        }

        if (!$this->tableExists($table)) {
            $this->columnCache[$table] = [];
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $this->columnCache[$table] = $cols;
        return $cols;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->getColumns($table), true);
    }

    private function getFirstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function getBioTableName(): ?string
    {
        foreach (['competances', 'competences'] as $table) {
            if ($this->tableExists($table) && $this->hasColumn($table, 'id_user') && $this->hasColumn($table, 'bio')) {
                return $table;
            }
        }

        return null;
    }

    private function hasRowsForUser(string $table, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$table} WHERE id_user = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getUserById(int $id): array|false
    {
        $profileColumnMap = [
            'specialite' => ['specialite'],
            'bio' => ['bio'],
            'experience' => ['experience'],
            'portfolio' => ['portfolio'],
            'ville' => ['ville'],
            'profil_telephone' => ['telephone'],
            'disponibilite' => ['disponibilite', 'statut'],
            'disponibilite_horaire' => ['disponibilite_horaire', 'horaires'],
            'disponibilite_message' => ['disponibilite_message'],
            'disponibilite_slots' => ['disponibilite_slots'],
            'disponibilite_exceptions' => ['disponibilite_exceptions'],
            'disponibilite_conges' => ['disponibilite_conges']
        ];
        $selectParts = ['u.*'];

        $canJoinProfile = $this->tableExists('profil_professionnel') && $this->hasColumn('profil_professionnel', 'id_user');

        foreach ($profileColumnMap as $alias => $candidates) {
            $column = $canJoinProfile ? $this->getFirstExistingColumn('profil_professionnel', $candidates) : null;
            if ($column !== null) {
                $selectParts[] = "p.$column AS $alias";
                continue;
            }

            $selectParts[] = "NULL AS $alias";
        }

        $sql = "SELECT " . implode(', ', $selectParts) . " FROM user u";
        if ($canJoinProfile) {
            $sql .= " LEFT JOIN profil_professionnel p ON p.id_user = u.id_user";
        }
        $sql .= " WHERE u.id_user = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getCompetences(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_competence, nom_competence, description, niveau, ordre
             FROM competences WHERE id_user = ? ORDER BY ordre, id_competence"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function addCompetence(int $userId, string $nom, string $description, int $niveau): void
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(ordre),0)+1 FROM competences WHERE id_user=?");
        $stmt->execute([$userId]);
        $ordre = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "INSERT INTO competences (id_user, nom_competence, description, niveau, ordre)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $nom, $description, $niveau, $ordre]);
    }

    public function deleteCompetence(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM competences WHERE id_competence = ? AND id_user = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateCompetence(int $id, int $userId, string $nom, string $description, int $niveau): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE competences
             SET nom_competence = ?, description = ?, niveau = ?
             WHERE id_competence = ? AND id_user = ?"
        );
        $stmt->execute([$nom, $description, $niveau, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function getBioByUserId(int $userId): string
    {
        $table = $this->getBioTableName();
        if ($table === null) {
            return '';
        }

        $stmt = $this->pdo->prepare("SELECT bio FROM {$table} WHERE id_user = ? AND bio IS NOT NULL AND TRIM(bio) <> '' LIMIT 1");
        $stmt->execute([$userId]);
        $bio = $stmt->fetchColumn();

        if (!is_string($bio)) {
            return '';
        }

        return trim($bio);
    }

    public function addBioForUser(int $userId, string $bio): bool
    {
        $table = $this->getBioTableName();
        if ($table === null || trim($bio) === '') {
            return false;
        }

        if (!$this->hasRowsForUser($table, $userId)) {
            return false;
        }

        if ($this->getBioByUserId($userId) !== '') {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE {$table} SET bio = ? WHERE id_user = ?");
        $stmt->execute([$bio, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateBioForUser(int $userId, string $bio): bool
    {
        $table = $this->getBioTableName();
        if ($table === null || trim($bio) === '') {
            return false;
        }

        if (!$this->hasRowsForUser($table, $userId)) {
            return false;
        }

        $currentBio = $this->getBioByUserId($userId);
        if ($currentBio === '') {
            return false;
        }

        if ($currentBio === trim($bio)) {
            return true;
        }

        $stmt = $this->pdo->prepare("UPDATE {$table} SET bio = ? WHERE id_user = ?");
        $stmt->execute([$bio, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteBioForUser(int $userId): bool
    {
        $table = $this->getBioTableName();
        if ($table === null) {
            return false;
        }

        if (!$this->hasRowsForUser($table, $userId)) {
            return false;
        }

        if ($this->getBioByUserId($userId) === '') {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE {$table} SET bio = NULL WHERE id_user = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }

    public function getStats(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM projet WHERE id_user = ?");
            $stmt->execute([$userId]);
            $projets = (int)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT AVG(note) FROM avis WHERE id_user_recepteur = ?");
            $stmt->execute([$userId]);
            $note = number_format((float)($stmt->fetchColumn() ?: 0), 1);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM application_offre WHERE id_mentor = ? AND statut = 'accepte'");
            $stmt->execute([$userId]);
            $mentores = (int)$stmt->fetchColumn();

            return compact('projets', 'note', 'mentores');
        } catch (Exception) {
            return ['projets' => 0, 'note' => '0.0', 'mentores' => 0];
        }
    }

    public function getCertifications(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_certification, nom_certification, niveau, ordre
             FROM certification WHERE id_user = ? ORDER BY ordre"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getExperiences(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM experience WHERE id_user = ? ORDER BY date_debut DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getPortfolioFiles(int $userId): array
    {
        $realisationSelect = $this->hasColumn('portfolio_files', 'realisation')
            ? 'realisation'
            : 'NULL AS realisation';

        $stmt = $this->pdo->prepare(
            "SELECT id_portfolio_file, titre, {$realisationSelect}, file_name, file_path, created_at
             FROM portfolio_files WHERE id_user = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function getAvis(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.*, u.prenom, u.nom
                 FROM avis a
                 JOIN user u ON a.id_user_auteur = u.id_user
                 WHERE a.id_user_recepteur = ?
                 ORDER BY a.date_creation DESC
                 LIMIT 5"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function addExperience(int $userId, string $poste, string $entreprise, string $debut, string $fin, string $desc): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO experience (id_user, poste, entreprise, date_debut, date_fin, description)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $poste, $entreprise, $debut, $fin, $desc]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteExperience(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM experience WHERE id_experience=? AND id_user=?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateExperience(int $id, int $userId, string $poste, string $entreprise, string $debut, string $fin, string $desc): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE experience SET poste=?, entreprise=?, date_debut=?, date_fin=?, description=?
             WHERE id_experience=? AND id_user=?"
        );
        $stmt->execute([$poste, $entreprise, $debut, $fin, $desc, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function addCertification(int $userId, string $nom, int $niveau): void
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(ordre),0)+1 FROM certification WHERE id_user=?");
        $stmt->execute([$userId]);
        $ordre = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "INSERT INTO certification (id_user, nom_certification, niveau, ordre)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $nom, $niveau, $ordre]);
    }

    public function deleteCertification(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM certification WHERE id_certification=? AND id_user=?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateCertification(int $id, int $userId, string $nom, int $niveau): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE certification SET nom_certification=?, niveau=?
             WHERE id_certification=? AND id_user=?"
        );
        $stmt->execute([$nom, $niveau, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateProfil(int $userId, array $data): void
    {
        $userFieldMap = [
            'nom' => ['nom'],
            'prenom' => ['prenom'],
            'email' => ['email'],
            'telephone' => ['telephone', 'num_tel']
        ];
        $userSets = [];
        $userParams = [];

        foreach ($userFieldMap as $dataKey => $candidates) {
            if (!array_key_exists($dataKey, $data)) {
                continue;
            }

            $column = $this->getFirstExistingColumn('user', $candidates);
            if ($column === null) {
                continue;
            }

            $userSets[] = "$column = ?";
            $userParams[] = $data[$dataKey];
        }

        if (!empty($userSets)) {
            $userParams[] = $userId;
            $stmt = $this->pdo->prepare("UPDATE user SET " . implode(', ', $userSets) . " WHERE id_user = ?");
            $stmt->execute($userParams);
        }

        if (!$this->tableExists('profil_professionnel') || !$this->hasColumn('profil_professionnel', 'id_user')) {
            return;
        }

        $profileFieldMap = [
            'specialite' => ['specialite'],
            'ville' => ['ville'],
            'telephone' => ['telephone'],
            'disponibilite' => ['disponibilite', 'statut'],
            'disponibilite_horaire' => ['disponibilite_horaire', 'horaires'],
            'disponibilite_message' => ['disponibilite_message'],
            'disponibilite_slots' => ['disponibilite_slots'],
            'disponibilite_exceptions' => ['disponibilite_exceptions'],
            'disponibilite_conges' => ['disponibilite_conges'],
            'bio' => ['bio'],
            'portfolio' => ['portfolio'],
            'experience' => ['experience']
        ];

        $statusLabels = [
            'disponible' => 'Disponible',
            'occupe' => 'Absent momentanement',
            'indisponible' => 'Indisponible'
        ];

        $profilePairs = [];
        foreach ($profileFieldMap as $dataKey => $candidates) {
            if (!array_key_exists($dataKey, $data)) {
                continue;
            }

            $column = $this->getFirstExistingColumn('profil_professionnel', $candidates);
            if ($column === null) {
                continue;
            }

            $value = $data[$dataKey];
            if ($dataKey === 'disponibilite' && $column === 'statut') {
                $normalized = strtolower(trim((string)$value));
                $value = $statusLabels[$normalized] ?? $value;
            }

            $profilePairs[$column] = $value;
        }

        if (empty($profilePairs)) {
            return;
        }

        $check = $this->pdo->prepare("SELECT 1 FROM profil_professionnel WHERE id_user = ? LIMIT 1");
        $check->execute([$userId]);
        $exists = (bool)$check->fetchColumn();

        if ($exists) {
            $profileSets = [];
            $profileParams = [];

            foreach ($profilePairs as $column => $value) {
                $profileSets[] = "$column = ?";
                $profileParams[] = $value;
            }

            $profileParams[] = $userId;
            $stmt = $this->pdo->prepare("UPDATE profil_professionnel SET " . implode(', ', $profileSets) . " WHERE id_user = ?");
            $stmt->execute($profileParams);
            return;
        }

        $insertColumns = ['id_user'];
        $insertExpressions = ['?'];
        $insertParams = [$userId];

        foreach ($profilePairs as $column => $value) {
            $insertColumns[] = $column;
            $insertExpressions[] = '?';
            $insertParams[] = $value;
        }

        if ($this->hasColumn('profil_professionnel', 'date_creation')) {
            $insertColumns[] = 'date_creation';
            $insertExpressions[] = 'CURDATE()';
        }

        $sql = "INSERT INTO profil_professionnel (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertExpressions) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($insertParams);
    }
}