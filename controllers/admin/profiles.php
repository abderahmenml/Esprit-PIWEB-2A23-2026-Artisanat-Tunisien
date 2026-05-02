<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$adminName = trim(((string)($_SESSION['prenom'] ?? '')) . ' ' . ((string)($_SESSION['nom'] ?? 'Admin')));

if (!isset($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['admin_csrf'];

$notice = '';
$noticeType = 'success';

$hasRoleColumn = admin_column_exists($pdo, 'user', 'role');
$hasEtatColumn = admin_column_exists($pdo, 'user', 'etat_compte');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $notice = 'Jeton de securite invalide.';
        $noticeType = 'danger';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'update_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $role = trim((string)($_POST['role'] ?? ''));
            $etat = trim((string)($_POST['etat_compte'] ?? ''));

            if ($userId <= 0) {
                $notice = 'Utilisateur invalide.';
                $noticeType = 'danger';
            } else {
                $updates = [];
                $params = [];

                if ($hasRoleColumn && $role !== '') {
                    $updates[] = 'role = ?';
                    $params[] = $role;
                }

                if ($hasEtatColumn && $etat !== '') {
                    $updates[] = 'etat_compte = ?';
                    $params[] = $etat;
                }

                if (!empty($updates)) {
                    $params[] = $userId;
                    $stmt = $pdo->prepare('UPDATE user SET ' . implode(', ', $updates) . ' WHERE id_user = ?');
                    $ok = $stmt->execute($params);
                    $notice = $ok ? 'Mise a jour effectuee.' : 'Mise a jour echouee.';
                    $noticeType = $ok ? 'success' : 'danger';
                } else {
                    $notice = 'Aucune modification detectee.';
                    $noticeType = 'warning';
                }
            }
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$filter = trim((string)($_GET['statut'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'latest'));

$hasProfile = admin_table_exists($pdo, 'profil_professionnel');
$profileFields = [];
foreach (['specialite', 'bio', 'ville', 'disponibilite'] as $column) {
    if ($hasProfile && admin_column_exists($pdo, 'profil_professionnel', $column)) {
        $profileFields[] = $column;
    }
}

$hasCompetences = admin_table_exists($pdo, 'competences') && admin_column_exists($pdo, 'competences', 'id_user');
$hasCertifications = admin_table_exists($pdo, 'certification') && admin_column_exists($pdo, 'certification', 'id_user');
$hasExperiences = admin_table_exists($pdo, 'experience') && admin_column_exists($pdo, 'experience', 'id_user');
$hasPortfolio = admin_table_exists($pdo, 'portfolio_files') && admin_column_exists($pdo, 'portfolio_files', 'id_user');
$hasOnboarding = admin_table_exists($pdo, 'artisan_onboarding');

$select = [
    'u.id_user',
    'u.nom',
    'u.prenom',
    'u.email',
    'u.date_creation'
];

if ($hasRoleColumn) {
    $select[] = 'u.role';
} else {
    $select[] = "'' AS role";
}

if ($hasEtatColumn) {
    $select[] = 'u.etat_compte';
} else {
    $select[] = "'actif' AS etat_compte";
}

if ($hasProfile) {
    foreach ($profileFields as $column) {
        $select[] = 'p.' . $column;
    }
}

$select[] = $hasCompetences ? '(SELECT COUNT(*) FROM competences c WHERE c.id_user = u.id_user) AS competence_count' : '0 AS competence_count';
$select[] = $hasCertifications ? '(SELECT COUNT(*) FROM certification c WHERE c.id_user = u.id_user) AS certification_count' : '0 AS certification_count';
$select[] = $hasExperiences ? '(SELECT COUNT(*) FROM experience e WHERE e.id_user = u.id_user) AS experience_count' : '0 AS experience_count';
$select[] = $hasPortfolio ? '(SELECT COUNT(*) FROM portfolio_files f WHERE f.id_user = u.id_user) AS portfolio_count' : '0 AS portfolio_count';
$select[] = $hasOnboarding ? 'ao.completed_at AS onboarding_completed_at' : 'NULL AS onboarding_completed_at';

$sql = 'SELECT ' . implode(', ', $select) . ' FROM user u';
if ($hasProfile) {
    $sql .= ' LEFT JOIN profil_professionnel p ON p.id_user = u.id_user';
}
if ($hasOnboarding) {
    $sql .= ' LEFT JOIN artisan_onboarding ao ON ao.user_id = u.id_user';
}

$params = [];
if ($search !== '') {
    $searchParts = ['u.prenom', 'u.nom', 'u.email'];
    if (in_array('specialite', $profileFields, true)) {
        $searchParts[] = 'p.specialite';
    }
    $sql .= ' WHERE CONCAT_WS(" ", ' . implode(', ', $searchParts) . ') LIKE ?';
    $params[] = '%' . $search . '%';
}

$orderBy = 'u.date_creation DESC';
if ($sort === 'oldest') {
    $orderBy = 'u.date_creation ASC';
} elseif ($sort === 'name_asc') {
    $orderBy = 'u.prenom ASC, u.nom ASC';
} elseif ($sort === 'name_desc') {
    $orderBy = 'u.prenom DESC, u.nom DESC';
}

$sql .= ' ORDER BY ' . $orderBy . ' LIMIT 400';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stats = ['total' => 0, 'actif' => 0, 'incomplet' => 0, 'suspendu' => 0];
$filteredRows = [];
$onboardingStats = ['done' => 0, 'pending' => 0];

$basePoints = count($profileFields)
    + ($hasCompetences ? 1 : 0)
    + ($hasCertifications ? 1 : 0)
    + ($hasExperiences ? 1 : 0)
    + ($hasPortfolio ? 1 : 0)
    + ($hasOnboarding ? 1 : 0);

foreach ($rows as $row) {
    $filled = 0;
    $missing = [];

    foreach ($profileFields as $column) {
        $value = trim((string)($row[$column] ?? ''));
        if ($value !== '') {
            $filled++;
        } else {
            $missing[] = ucfirst($column);
        }
    }

    if ($hasCompetences) {
        if ((int)$row['competence_count'] > 0) {
            $filled++;
        } else {
            $missing[] = 'Competences';
        }
    }

    if ($hasCertifications) {
        if ((int)$row['certification_count'] > 0) {
            $filled++;
        } else {
            $missing[] = 'Certifications';
        }
    }

    if ($hasExperiences) {
        if ((int)$row['experience_count'] > 0) {
            $filled++;
        } else {
            $missing[] = 'Experiences';
        }
    }

    if ($hasPortfolio) {
        if ((int)$row['portfolio_count'] > 0) {
            $filled++;
        } else {
            $missing[] = 'Portfolio';
        }
    }

    $onboardingDone = true;
    if ($hasOnboarding) {
        $onboardingDone = !empty($row['onboarding_completed_at']);
        if ($onboardingDone) {
            $filled++;
        } else {
            $missing[] = 'Onboarding';
        }
    }

    if ($hasOnboarding) {
        if ($onboardingDone) {
            $onboardingStats['done']++;
        } else {
            $onboardingStats['pending']++;
        }
    }

    $completion = $basePoints > 0 ? (int)round(($filled / $basePoints) * 100) : 0;
    $etat = strtolower(trim((string)($row['etat_compte'] ?? 'actif')));

    if ($etat !== '' && !in_array($etat, ['actif', 'active'], true)) {
        $statut = 'suspendu';
    } else {
        $statut = $completion >= 70 ? 'actif' : 'incomplet';
    }

    $stats['total']++;
    if (isset($stats[$statut])) {
        $stats[$statut]++;
    }

    if ($filter !== '' && $filter !== $statut) {
        continue;
    }

    $row['completion'] = $completion;
    $row['missing'] = $missing;
    $row['statut'] = $statut;
    $row['onboarding_done'] = $onboardingDone;
    $filteredRows[] = $row;
}

$profileTrend = admin_build_daily_trend($rows, 'date_creation', 7);

if (in_array($sort, ['completion_desc', 'completion_asc'], true)) {
    usort($filteredRows, static function (array $a, array $b) use ($sort): int {
        if ($a['completion'] === $b['completion']) {
            return strcmp((string)($b['date_creation'] ?? ''), (string)($a['date_creation'] ?? ''));
        }
        return $sort === 'completion_desc'
            ? $b['completion'] <=> $a['completion']
            : $a['completion'] <=> $b['completion'];
    });
}

require dirname(__DIR__, 2) . '/views/backend/admin/profiles.php';
