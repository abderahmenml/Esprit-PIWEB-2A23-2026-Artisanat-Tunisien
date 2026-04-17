<?php
// controllers/AdminController.php

require_once 'config/config.php';

class AdminController
{
    private function redirect(string $path): void
    {
        header('Location: ' . app_url($path));
        exit;
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }

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
        $search = trim((string)($_GET['q'] ?? ''));
        $filter = trim((string)($_GET['statut'] ?? ''));

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
            $userTable = '';
            $userTables = ['user', 'users', 'utilisateur', 'utilisateurs'];
            foreach ($userTables as $table) {
                $checkUser = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($checkUser && $checkUser->rowCount() > 0) {
                    $userTable = $table;
                    break;
                }
            }

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

        $filteredRows = $rows;
        if (in_array($filter, ['actif', 'incomplet', 'suspendu'], true)) {
            $filteredRows = array_values(array_filter($rows, function ($row) use ($filter) {
                return $row['statut'] === $filter;
            }));
        }

        $activePercent = $stats['total'] > 0 ? (int)round(($stats['actif'] / $stats['total']) * 100) : 0;
        $incompletePercent = $stats['total'] > 0 ? (int)round(($stats['incomplet'] / $stats['total']) * 100) : 0;
        $searchQuery = $search !== '' ? '&q=' . urlencode($search) : '';
        $tousHref = app_url('/admin') . ($search !== '' ? '?q=' . urlencode($search) : '');

        require_once 'views/admin/index.php';
    }

    public function deleteUser()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin');
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $_SESSION['flash_admin'] = ['type' => 'error', 'msg' => 'ID invalide.'];
            $this->redirect('/admin');
        }

        $pdo = getPDO();

        function table_exists($pdo, $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            return $stmt && $stmt->rowCount() > 0;
        }

        function column_exists($pdo, $table, $column) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        }

        function delete_by_column($pdo, $table, $column, $id) {
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$column` = ?");
            $stmt->execute([$id]);
        }

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
                if (table_exists($pdo, $item['table']) && column_exists($pdo, $item['table'], $item['column'])) {
                    delete_by_column($pdo, $item['table'], $item['column'], $id);
                }
            }

            if (table_exists($pdo, 'avis')) {
                $clauses = [];
                $params = [];
                if (column_exists($pdo, 'avis', 'id_user')) {
                    $clauses[] = 'id_user = ?';
                    $params[] = $id;
                }
                if (column_exists($pdo, 'avis', 'id_user_recepteur')) {
                    $clauses[] = 'id_user_recepteur = ?';
                    $params[] = $id;
                }
                if (column_exists($pdo, 'avis', 'id_user_auteur')) {
                    $clauses[] = 'id_user_auteur = ?';
                    $params[] = $id;
                }
                if (!empty($clauses)) {
                    $stmt = $pdo->prepare('DELETE FROM avis WHERE ' . implode(' OR ', $clauses));
                    $stmt->execute($params);
                }
            }

            if (table_exists($pdo, 'application_offre')) {
                $clauses = [];
                $params = [];
                if (column_exists($pdo, 'application_offre', 'id_user')) {
                    $clauses[] = 'id_user = ?';
                    $params[] = $id;
                }
                if (column_exists($pdo, 'application_offre', 'id_mentor')) {
                    $clauses[] = 'id_mentor = ?';
                    $params[] = $id;
                }
                if (!empty($clauses)) {
                    $stmt = $pdo->prepare('DELETE FROM application_offre WHERE ' . implode(' OR ', $clauses));
                    $stmt->execute($params);
                }
            }

            if (table_exists($pdo, 'mentorat')) {
                $clauses = [];
                $params = [];
                if (column_exists($pdo, 'mentorat', 'mentor_id')) {
                    $clauses[] = 'mentor_id = ?';
                    $params[] = $id;
                }
                if (column_exists($pdo, 'mentorat', 'id_user')) {
                    $clauses[] = 'id_user = ?';
                    $params[] = $id;
                }
                if (!empty($clauses)) {
                    $stmt = $pdo->prepare('DELETE FROM mentorat WHERE ' . implode(' OR ', $clauses));
                    $stmt->execute($params);
                }
            }

            $userTable = '';
            $userTables = ['user', 'users', 'utilisateur', 'utilisateurs'];
            foreach ($userTables as $table) {
                if (table_exists($pdo, $table) && column_exists($pdo, $table, 'id_user')) {
                    $userTable = $table;
                    break;
                }
            }

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
