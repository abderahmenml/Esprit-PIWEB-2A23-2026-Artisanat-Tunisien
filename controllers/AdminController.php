<?php
// controllers/AdminController.php

require_once 'config/config.php';
require_once __DIR__ . '/../models/ProfilModel.php';

class AdminController   
{
    private function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return $stmt->fetchColumn() !== false;
        } catch (Exception) {
            return false;
        }
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception) {
            return false;
        }
    }

    private function resolveUserTable(PDO $pdo): string
    {
        foreach (['user', 'users', 'utilisateur', 'utilisateurs'] as $table) {
            if ($this->tableExists($pdo, $table) && $this->columnExists($pdo, $table, 'id_user')) {
                return $table;
            }
        }

        return '';
    }

    private function isValidName(string $value): bool
    {
        return preg_match("/^[\\p{L}\\s\\-'’]{2,60}$/u", $value) === 1;
    }

    private function isValidEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPhone(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        if (preg_match('/^\+?[0-9][0-9\s().-]{7,19}$/', $value) !== 1) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        $count = strlen($digits);
        return $count >= 8 && $count <= 15;
    }

    private function flashAdmin(string $type, string $message): void
    {
        $_SESSION['flash_admin'] = ['type' => $type, 'msg' => $message];
    }

    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }
    }

    private function normalizeText(?string $value, int $maxLength = 255): string
    {
        $text = trim((string)$value);
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        if ($maxLength > 0 && function_exists('mb_strlen') && mb_strlen($text) > $maxLength) {
            return (string)mb_substr($text, 0, $maxLength);
        }
        if ($maxLength > 0 && strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength);
        }

        return $text;
    }

    private function redirect(string $path): void
    {
        header('Location: ' . app_url($path));
        exit;
    }

    public function index()
    {
        $this->requireAuth();

        $flashAdmin = null;
        $flashAdminClass = '#ffebee';
        $flashAdminText = '#b71c1c';
        if (isset($_SESSION['flash_admin'])) {
            $flashAdmin = $_SESSION['flash_admin'];
            unset($_SESSION['flash_admin']);
            if (isset($flashAdmin['type']) && $flashAdmin['type'] === 'success') {
                $flashAdminClass = '#e8f5e9';
                $flashAdminText = '#2e7d32';
            }
        }

        $pdo = getPDO();
        $profilModel = new ProfilModel();
        $search = trim((string)($_GET['q'] ?? ''));
        $filter = trim((string)($_GET['statut'] ?? ''));
        $sort = trim((string)($_GET['sort'] ?? 'date_desc'));
        $specialiteFilter = trim((string)($_GET['specialite'] ?? ''));
        $competenceCatalog = $profilModel->getCompetenceCatalog();
        $competenceCatalogCount = count($competenceCatalog);

        $rows = [];
        $errorMsg = '';
        $profileCols = [
            'specialite' => false,
            'bio' => false,
            'experience' => false,
            'portfolio' => false,
            'ville' => false
        ];
        try {
            $userTable = $this->resolveUserTable($pdo);

            if ($userTable === '') {
                $errorMsg = 'Table utilisateur introuvable.';
            } else {
                $hasProfile = false;
                $hasCompetences = false;

                $checkProfile = $pdo->query("SHOW TABLES LIKE 'profil_professionnel'");
                if ($checkProfile && $checkProfile->rowCount() > 0) {
                    $hasProfile = true;
                }

                if ($hasProfile) {
                    $colStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'profil_professionnel'");
                    $colStmt->execute();
                    $existing = $colStmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($profileCols as $col => $present) {
                        if (in_array($col, $existing, true)) {
                            $profileCols[$col] = true;
                        }
                    }
                }

                $checkComp = $pdo->query("SHOW TABLES LIKE 'competences'");
                if ($checkComp && $checkComp->rowCount() > 0) {
                    $hasCompetences = true;
                }

                $sql = "SELECT u.id_user, u.nom, u.prenom, u.email, u.date_creation";
                foreach ($profileCols as $col => $present) {
                    if ($hasProfile && $present) {
                        $sql .= ", p.$col";
                    } else {
                        $sql .= ", NULL AS $col";
                    }
                }

                if ($hasCompetences) {
                    $sql .= ", (SELECT GROUP_CONCAT(c.nom_competence ORDER BY c.ordre ASC SEPARATOR ', ') FROM competences c WHERE c.id_user = u.id_user) AS competences";
                    $sql .= ", (SELECT COUNT(*) FROM competences c WHERE c.id_user = u.id_user) AS competence_count";
                } else {
                    $sql .= ", '' AS competences, 0 AS competence_count";
                }

                $sql .= " FROM `$userTable` u";
                if ($hasProfile) {
                    $sql .= " LEFT JOIN profil_professionnel p ON p.id_user = u.id_user";
                }

                $params = [];
                if ($search !== '') {
                    $searchParts = ['u.prenom', 'u.nom', 'u.email'];
                    if ($hasProfile && $profileCols['specialite']) {
                        $searchParts[] = 'p.specialite';
                    }
                    $sql .= " WHERE CONCAT_WS(' ', " . implode(', ', $searchParts) . ") LIKE ?";
                    $params[] = '%' . $search . '%';
                }

                $sql .= " ORDER BY u.date_creation DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            $rows = [];
            $errorMsg = 'Erreur SQL : ' . $e->getMessage();
        }

        $colors = ['#8B5A3A', '#2E6B3E', '#C49A6C', '#5C3320', '#3B2314'];
        $stats = ['total' => 0, 'actif' => 0, 'incomplet' => 0, 'suspendu' => 0];

        foreach ($rows as &$row) {
            $filled = 0;
            $fields = array_keys(array_filter($profileCols));
            foreach ($fields as $field) {
                if (!empty($row[$field])) {
                    $filled++;
                }
            }
            if (count($fields) > 0) {
                $completion = (int)round(($filled / count($fields)) * 100);
            } else {
                $completion = 0;
            }
            if ((int)$row['competence_count'] > 0) {
                $completion = min(100, $completion + 10);
            }
            $row['completion'] = $completion;
            $row['statut'] = $completion >= 70 ? 'actif' : 'incomplet';
            $row['competence_list'] = $row['competences'] ? explode(', ', $row['competences']) : [];
            $row['color'] = $colors[$row['id_user'] % count($colors)];

            $stats['total']++;
            if ($row['statut'] === 'actif') {
                $stats['actif']++;
            } else {
                $stats['incomplet']++;
            }
        }
        unset($row);

        $availableSpecialites = [];
        foreach ($rows as $row) {
            $value = trim((string)($row['specialite'] ?? ''));
            if ($value !== '' && !in_array($value, $availableSpecialites, true)) {
                $availableSpecialites[] = $value;
            }
        }
        sort($availableSpecialites);

        $filteredRows = $rows;
        if ($specialiteFilter !== '') {
            $needle = mb_strtolower($specialiteFilter, 'UTF-8');
            $filteredRows = array_values(array_filter($filteredRows, static function (array $row) use ($needle): bool {
                $specialite = mb_strtolower(trim((string)($row['specialite'] ?? '')), 'UTF-8');
                return $specialite === $needle;
            }));
        }

        if (in_array($filter, ['actif', 'incomplet', 'suspendu'], true)) {
            $filteredRows = array_values(array_filter($rows, function ($row) use ($filter) {
                return $row['statut'] === $filter;
            }));
            if ($specialiteFilter !== '') {
                $needle = mb_strtolower($specialiteFilter, 'UTF-8');
                $filteredRows = array_values(array_filter($filteredRows, static function (array $row) use ($needle): bool {
                    $specialite = mb_strtolower(trim((string)($row['specialite'] ?? '')), 'UTF-8');
                    return $specialite === $needle;
                }));
            }
        }

        $sorters = [
            'date_desc' => static fn(array $a, array $b): int => strcmp((string)($b['date_creation'] ?? ''), (string)($a['date_creation'] ?? '')),
            'date_asc' => static fn(array $a, array $b): int => strcmp((string)($a['date_creation'] ?? ''), (string)($b['date_creation'] ?? '')),
            'nom_asc' => static fn(array $a, array $b): int => strcmp(mb_strtolower(trim((string)($a['prenom'] ?? '') . ' ' . (string)($a['nom'] ?? '')), 'UTF-8'), mb_strtolower(trim((string)($b['prenom'] ?? '') . ' ' . (string)($b['nom'] ?? '')), 'UTF-8')),
            'nom_desc' => static fn(array $a, array $b): int => strcmp(mb_strtolower(trim((string)($b['prenom'] ?? '') . ' ' . (string)($b['nom'] ?? '')), 'UTF-8'), mb_strtolower(trim((string)($a['prenom'] ?? '') . ' ' . (string)($a['nom'] ?? '')), 'UTF-8')),
            'completion_desc' => static fn(array $a, array $b): int => ((int)($b['completion'] ?? 0)) <=> ((int)($a['completion'] ?? 0)),
            'completion_asc' => static fn(array $a, array $b): int => ((int)($a['completion'] ?? 0)) <=> ((int)($b['completion'] ?? 0)),
        ];

        if (!isset($sorters[$sort])) {
            $sort = 'date_desc';
        }
        usort($filteredRows, $sorters[$sort]);

        $activePercent = $stats['total'] > 0 ? (int)round(($stats['actif'] / $stats['total']) * 100) : 0;
        $incompletePercent = $stats['total'] > 0 ? (int)round(($stats['incomplet'] / $stats['total']) * 100) : 0;
        $queryBase = [];
        if ($search !== '') {
            $queryBase['q'] = $search;
        }
        if ($specialiteFilter !== '') {
            $queryBase['specialite'] = $specialiteFilter;
        }
        if ($sort !== '') {
            $queryBase['sort'] = $sort;
        }

        $queryWithStatus = $queryBase;
        $searchQuery = '&' . http_build_query($queryBase);
        $tousHref = app_url('/admin') . (!empty($queryBase) ? '?' . http_build_query($queryBase) : '');
        $actifHref = app_url('/admin') . '?' . http_build_query(array_merge($queryWithStatus, ['statut' => 'actif']));
        $incompletHref = app_url('/admin') . '?' . http_build_query(array_merge($queryWithStatus, ['statut' => 'incomplet']));
        $suspenduHref = app_url('/admin') . '?' . http_build_query(array_merge($queryWithStatus, ['statut' => 'suspendu']));

        require_once 'views/admin/index.php';
    }

    public function addUser(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin');
        }

        $nom = $this->normalizeText($_POST['nom'] ?? '', 60);
        $prenom = $this->normalizeText($_POST['prenom'] ?? '', 60);
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $role = $this->normalizeText($_POST['role'] ?? 'utilisateur', 40);
        $specialite = $this->normalizeText($_POST['specialite'] ?? '', 120);
        $ville = $this->normalizeText($_POST['ville'] ?? '', 120);

        if (!$this->isValidName($nom) || !$this->isValidName($prenom)) {
            $this->flashAdmin('error', 'Nom ou prenom invalide.');
            $this->redirect('/admin');
        }
        if (!$this->isValidEmail($email)) {
            $this->flashAdmin('error', 'Email invalide.');
            $this->redirect('/admin');
        }
        if (strlen($password) < 8) {
            $this->flashAdmin('error', 'Mot de passe invalide (8 caracteres minimum).');
            $this->redirect('/admin');
        }

        try {
            $pdo = getPDO();
            $userTable = $this->resolveUserTable($pdo);
            if ($userTable === '') {
                $this->flashAdmin('error', 'Table utilisateur introuvable.');
                $this->redirect('/admin');
            }

            $stmtExisting = $pdo->prepare("SELECT COUNT(*) FROM `$userTable` WHERE email = ?");
            $stmtExisting->execute([$email]);
            if ((int)$stmtExisting->fetchColumn() > 0) {
                $this->flashAdmin('error', 'Cet email existe deja.');
                $this->redirect('/admin');
            }

            $columns = ['nom', 'prenom', 'email', 'mot_de_passe'];
            $values = ['?', '?', '?', '?'];
            $params = [$nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT)];

            if ($this->columnExists($pdo, $userTable, 'role')) {
                $columns[] = 'role';
                $values[] = '?';
                $params[] = $role !== '' ? $role : 'utilisateur';
            }
            if ($this->columnExists($pdo, $userTable, 'etat_compte')) {
                $columns[] = 'etat_compte';
                $values[] = "'actif'";
            }
            if ($this->columnExists($pdo, $userTable, 'date_creation')) {
                $columns[] = 'date_creation';
                $values[] = 'CURDATE()';
            }

            $sql = 'INSERT INTO `' . $userTable . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
            $stmtInsert = $pdo->prepare($sql);
            $stmtInsert->execute($params);
            $userId = (int)$pdo->lastInsertId();

            if ($userId > 0 && $this->tableExists($pdo, 'profil_professionnel')) {
                $profileColumns = ['id_user'];
                $profileValues = ['?'];
                $profileParams = [$userId];

                if ($this->columnExists($pdo, 'profil_professionnel', 'specialite')) {
                    $profileColumns[] = 'specialite';
                    $profileValues[] = '?';
                    $profileParams[] = $specialite;
                }
                if ($this->columnExists($pdo, 'profil_professionnel', 'ville')) {
                    $profileColumns[] = 'ville';
                    $profileValues[] = '?';
                    $profileParams[] = $ville;
                }
                if ($this->columnExists($pdo, 'profil_professionnel', 'date_creation')) {
                    $profileColumns[] = 'date_creation';
                    $profileValues[] = 'CURDATE()';
                }

                $sqlProfile = 'INSERT INTO profil_professionnel (' . implode(', ', $profileColumns) . ') VALUES (' . implode(', ', $profileValues) . ')';
                $stmtProfile = $pdo->prepare($sqlProfile);
                $stmtProfile->execute($profileParams);
            }

            $this->flashAdmin('success', 'Nouveau profil cree avec succes.');
        } catch (Exception) {
            $this->flashAdmin('error', 'Creation du profil impossible.');
        }

        $this->redirect('/admin');
    }

    public function updateUser(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin');
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nom = $this->normalizeText($_POST['nom'] ?? '', 60);
        $prenom = $this->normalizeText($_POST['prenom'] ?? '', 60);
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $role = $this->normalizeText($_POST['role'] ?? '', 40);
        $specialite = $this->normalizeText($_POST['specialite'] ?? '', 120);
        $ville = $this->normalizeText($_POST['ville'] ?? '', 120);
        $telephone = $this->normalizeText($_POST['telephone'] ?? '', 30);

        if (!$id || !$this->isValidName($nom) || !$this->isValidName($prenom)) {
            $this->flashAdmin('error', 'Parametres invalides.');
            $this->redirect('/admin');
        }
        if (!$this->isValidEmail($email)) {
            $this->flashAdmin('error', 'Email invalide.');
            $this->redirect('/admin');
        }
        if (!$this->isValidPhone($telephone)) {
            $this->flashAdmin('error', 'Telephone invalide.');
            $this->redirect('/admin');
        }

        try {
            $pdo = getPDO();
            $userTable = $this->resolveUserTable($pdo);
            if ($userTable === '') {
                $this->flashAdmin('error', 'Table utilisateur introuvable.');
                $this->redirect('/admin');
            }

            $checkUser = $pdo->prepare("SELECT COUNT(*) FROM `$userTable` WHERE id_user = ?");
            $checkUser->execute([$id]);
            if ((int)$checkUser->fetchColumn() === 0) {
                $this->flashAdmin('error', 'Utilisateur introuvable.');
                $this->redirect('/admin');
            }

            $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM `$userTable` WHERE email = ? AND id_user <> ?");
            $checkEmail->execute([$email, $id]);
            if ((int)$checkEmail->fetchColumn() > 0) {
                $this->flashAdmin('error', 'Cet email est deja utilise.');
                $this->redirect('/admin');
            }

            $setParts = ['nom = ?', 'prenom = ?', 'email = ?'];
            $params = [$nom, $prenom, $email];

            if ($role !== '' && $this->columnExists($pdo, $userTable, 'role')) {
                $setParts[] = 'role = ?';
                $params[] = $role;
            }

            $params[] = $id;
            $stmtUpdate = $pdo->prepare('UPDATE `' . $userTable . '` SET ' . implode(', ', $setParts) . ' WHERE id_user = ?');
            $stmtUpdate->execute($params);

            if ($this->tableExists($pdo, 'profil_professionnel')) {
                $profileUpdate = [];
                $profileParams = [];

                if ($this->columnExists($pdo, 'profil_professionnel', 'specialite')) {
                    $profileUpdate[] = 'specialite = ?';
                    $profileParams[] = $specialite;
                }
                if ($this->columnExists($pdo, 'profil_professionnel', 'ville')) {
                    $profileUpdate[] = 'ville = ?';
                    $profileParams[] = $ville;
                }
                if ($this->columnExists($pdo, 'profil_professionnel', 'telephone')) {
                    $profileUpdate[] = 'telephone = ?';
                    $profileParams[] = $telephone;
                }

                if (!empty($profileUpdate)) {
                    $stmtExistsProfile = $pdo->prepare('SELECT COUNT(*) FROM profil_professionnel WHERE id_user = ?');
                    $stmtExistsProfile->execute([$id]);

                    if ((int)$stmtExistsProfile->fetchColumn() > 0) {
                        $profileParams[] = $id;
                        $stmtUpdateProfile = $pdo->prepare('UPDATE profil_professionnel SET ' . implode(', ', $profileUpdate) . ' WHERE id_user = ?');
                        $stmtUpdateProfile->execute($profileParams);
                    } else {
                        $insertCols = ['id_user'];
                        $insertVals = ['?'];
                        $insertParams = [$id];

                        if ($this->columnExists($pdo, 'profil_professionnel', 'specialite')) {
                            $insertCols[] = 'specialite';
                            $insertVals[] = '?';
                            $insertParams[] = $specialite;
                        }
                        if ($this->columnExists($pdo, 'profil_professionnel', 'ville')) {
                            $insertCols[] = 'ville';
                            $insertVals[] = '?';
                            $insertParams[] = $ville;
                        }
                        if ($this->columnExists($pdo, 'profil_professionnel', 'telephone')) {
                            $insertCols[] = 'telephone';
                            $insertVals[] = '?';
                            $insertParams[] = $telephone;
                        }
                        if ($this->columnExists($pdo, 'profil_professionnel', 'date_creation')) {
                            $insertCols[] = 'date_creation';
                            $insertVals[] = 'CURDATE()';
                        }

                        $stmtInsertProfile = $pdo->prepare('INSERT INTO profil_professionnel (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')');
                        $stmtInsertProfile->execute($insertParams);
                    }
                }
            }

            $this->flashAdmin('success', 'Profil mis a jour avec succes.');
        } catch (Exception) {
            $this->flashAdmin('error', 'Mise a jour du profil impossible.');
        }

        $this->redirect('/admin');
    }

    public function competences(): void
    {
        $this->requireAuth();

        $flashAdmin = null;
        if (isset($_SESSION['flash_admin'])) {
            $flashAdmin = $_SESSION['flash_admin'];
            unset($_SESSION['flash_admin']);
        }

        $rows = [];
        $errorMsg = '';
        try {
            $pdo = getPDO();
            $check = $pdo->query("SHOW TABLES LIKE 'competences'");
            if (!$check || $check->rowCount() === 0) {
                $errorMsg = 'Table competences introuvable.';
            } else {
                $stmt = $pdo->query("\n                    SELECT id_competence, nom_competence, description\n                    FROM competences\n                    ORDER BY nom_competence ASC, id_competence ASC\n                ");
                $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            }
        } catch (Exception $e) {
            $errorMsg = 'Erreur SQL : ' . $e->getMessage();
        }

        require_once 'views/admin/competences.php';
    }

    public function addCompetence(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/competences');
        }

        $nom = $this->normalizeText($_POST['nom'] ?? '', 100);
        $description = $this->normalizeText($_POST['description'] ?? '', 500);

        if ($nom === '') {
            $this->flashAdmin('error', 'Nom de competence obligatoire.');
            $this->redirect('/admin/competences');
        }

        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("\n                INSERT INTO competences (nom_competence, description, id_user, niveau, ordre)\n                VALUES (?, ?, 0, 100, 0)\n            ");
            $stmt->execute([$nom, $description]);
            $this->flashAdmin('success', 'Competence ajoutee.');
        } catch (Exception) {
            $this->flashAdmin('error', 'Ajout impossible.');
        }

        $this->redirect('/admin/competences');
    }

    public function updateCompetence(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/competences');
        }

        $id = filter_input(INPUT_POST, 'id_competence', FILTER_VALIDATE_INT);
        $nom = $this->normalizeText($_POST['nom'] ?? '', 100);
        $description = $this->normalizeText($_POST['description'] ?? '', 500);

        if (!$id || $nom === '') {
            $this->flashAdmin('error', 'Parametres invalides.');
            $this->redirect('/admin/competences');
        }

        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("\n                UPDATE competences\n                SET nom_competence = ?, description = ?\n                WHERE id_competence = ?\n            ");
            $stmt->execute([$nom, $description, $id]);
            $this->flashAdmin('success', 'Competence modifiee.');
        } catch (Exception) {
            $this->flashAdmin('error', 'Modification impossible.');
        }

        $this->redirect('/admin/competences');
    }

    public function deleteCompetence(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/competences');
        }

        $id = filter_input(INPUT_POST, 'id_competence', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->flashAdmin('error', 'ID invalide.');
            $this->redirect('/admin/competences');
        }

        try {
            $pdo = getPDO();
            foreach (['profil_competence', 'profil_competences'] as $pivot) {
                $checkPivot = $pdo->query("SHOW TABLES LIKE '$pivot'");
                if (!$checkPivot || $checkPivot->rowCount() === 0) {
                    continue;
                }

                foreach (['id_competence', 'id_competences'] as $pivotColumn) {
                    $stmtCol = $pdo->prepare("\n                        SELECT COUNT(*)\n                        FROM INFORMATION_SCHEMA.COLUMNS\n                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?\n                    ");
                    $stmtCol->execute([$pivot, $pivotColumn]);
                    if ((int)$stmtCol->fetchColumn() > 0) {
                        $stmtDeletePivot = $pdo->prepare("DELETE FROM `$pivot` WHERE `$pivotColumn` = ?");
                        $stmtDeletePivot->execute([$id]);
                        break;
                    }
                }
            }

            $stmt = $pdo->prepare("DELETE FROM competences WHERE id_competence = ?");
            $stmt->execute([$id]);
            $this->flashAdmin('success', 'Competence supprimee.');
        } catch (Exception) {
            $this->flashAdmin('error', 'Suppression impossible.');
        }

        $this->redirect('/admin/competences');
    }

    public function deleteUser()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin');
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $_SESSION['flash_admin'] = ['type' => 'error', 'msg' => 'ID invalide.'];
            $this->redirect('/admin');
        }

        $pdo = getPDO();

        try {
            $pdo->beginTransaction();

            $cleanup = [
                ['table' => 'profil_professionnel', 'column' => 'id_user'],
                ['table' => 'competences', 'column' => 'id_user'],
                ['table' => 'competence', 'column' => 'id_user'],
                ['table' => 'certification', 'column' => 'id_user'],
                ['table' => 'experience', 'column' => 'id_user'],
                ['table' => 'experiences', 'column' => 'id_user'],
                ['table' => 'portfolio_files', 'column' => 'id_user'],
                ['table' => 'projet', 'column' => 'id_user']
            ];

            foreach ($cleanup as $item) {
                if ($this->tableExists($pdo, $item['table']) && $this->columnExists($pdo, $item['table'], $item['column'])) {
                    $stmtDelete = $pdo->prepare("DELETE FROM `{$item['table']}` WHERE `{$item['column']}` = ?");
                    $stmtDelete->execute([$id]);
                }
            }

            $idProfil = null;
            if ($this->tableExists($pdo, 'profil_professionnel') && $this->columnExists($pdo, 'profil_professionnel', 'id_profil')) {
                $stmtProfil = $pdo->prepare('SELECT id_profil FROM profil_professionnel WHERE id_user = ? LIMIT 1');
                $stmtProfil->execute([$id]);
                $fetchedProfil = $stmtProfil->fetchColumn();
                if ($fetchedProfil !== false) {
                    $idProfil = (int)$fetchedProfil;
                }
            }

            if ($idProfil !== null) {
                foreach (['profil_competence', 'profil_competences'] as $pivotTable) {
                    if ($this->tableExists($pdo, $pivotTable) && $this->columnExists($pdo, $pivotTable, 'id_profil')) {
                        $stmtDeletePivot = $pdo->prepare("DELETE FROM `$pivotTable` WHERE `id_profil` = ?");
                        $stmtDeletePivot->execute([$idProfil]);
                    }
                }
            }

            if ($this->tableExists($pdo, 'avis')) {
                $clauses = [];
                $params = [];
                if ($this->columnExists($pdo, 'avis', 'id_user')) {
                    $clauses[] = 'id_user = ?';
                    $params[] = $id;
                }
                if ($this->columnExists($pdo, 'avis', 'id_user_recepteur')) {
                    $clauses[] = 'id_user_recepteur = ?';
                    $params[] = $id;
                }
                if ($this->columnExists($pdo, 'avis', 'id_user_auteur')) {
                    $clauses[] = 'id_user_auteur = ?';
                    $params[] = $id;
                }
                if (!empty($clauses)) {
                    $stmt = $pdo->prepare('DELETE FROM avis WHERE ' . implode(' OR ', $clauses));
                    $stmt->execute($params);
                }
            }

            if ($this->tableExists($pdo, 'application_offre')) {
                $clauses = [];
                $params = [];
                if ($this->columnExists($pdo, 'application_offre', 'id_user')) {
                    $clauses[] = 'id_user = ?';
                    $params[] = $id;
                }
                if ($this->columnExists($pdo, 'application_offre', 'id_mentor')) {
                    $clauses[] = 'id_mentor = ?';
                    $params[] = $id;
                }
                if (!empty($clauses)) {
                    $stmt = $pdo->prepare('DELETE FROM application_offre WHERE ' . implode(' OR ', $clauses));
                    $stmt->execute($params);
                }
            }

            if ($this->tableExists($pdo, 'mentorat')) {
                $clauses = [];
                $params = [];
                if ($this->columnExists($pdo, 'mentorat', 'mentor_id')) {
                    $clauses[] = 'mentor_id = ?';
                    $params[] = $id;
                }
                if ($this->columnExists($pdo, 'mentorat', 'id_user')) {
                    $clauses[] = 'id_user = ?';
                    $params[] = $id;
                }
                if (!empty($clauses)) {
                    $stmt = $pdo->prepare('DELETE FROM mentorat WHERE ' . implode(' OR ', $clauses));
                    $stmt->execute($params);
                }
            }

            $userTable = $this->resolveUserTable($pdo);

            if ($userTable === '') {
                $pdo->rollBack();
                $_SESSION['flash_admin'] = ['type' => 'error', 'msg' => 'Table utilisateur introuvable.'];
                $this->redirect('/admin');
            }

            $stmt = $pdo->prepare("DELETE FROM `$userTable` WHERE id_user = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            $_SESSION['flash_admin'] = ['type' => 'success', 'msg' => 'Utilisateur supprime.'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_admin'] = ['type' => 'error', 'msg' => 'Suppression impossible.'];
        }

        $this->redirect('/admin');
    }
}
?>
