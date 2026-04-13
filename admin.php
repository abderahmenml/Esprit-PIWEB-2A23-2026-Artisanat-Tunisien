<?php
session_start();
require_once 'config.php';

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
$tousHref = $search !== '' ? 'admin.php?q=' . urlencode($search) : 'admin.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CraftLink — Back Office · Profils Professionnels</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
         :root {
            --marron: #8B5A3A;
            --vert: #2E6B3E;
            --caramel: #C49A6C;
            --creme: #F5ECD7;
            --brun: #3B2314;
            --brun-mid: #5C3320;
            --bg: #1a1008;
            --sidebar: #241408;
            --card: #2e1a0e;
            --border: rgba(139, 90, 58, .15);
            --text: #e8d5bc;
            --muted: rgba(59, 35, 20, .55);
            --shadow: rgba(0, 0, 0, .08);
            --red: #e05252;
            --green: #4caf7d;
            --orange: #f0a050;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(180deg, #FDF9F3, #F8F2E6);
            color: var(--text);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            display: flex;
        }
        /* SIDEBAR */
        
        .sidebar {
            width: 230px;
            flex-shrink: 0;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
        }
        
        .sidebar-logo {
            padding: 1.5rem 1.2rem;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--caramel);
            font-weight: 900;
        }
        
        .sidebar-logo h1 span {
            color: var(--vert);
        }
        
        .sidebar-logo .sub {
            font-size: .7rem;
            color: var(--muted);
            margin-top: .2rem;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        
        .nav-section {
            padding: 1rem 0;
        }
        
        .nav-label {
            font-size: .68rem;
            color: var(--muted);
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .4rem 1.2rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .65rem 1.2rem;
            font-size: .88rem;
            color: var(--muted);
            cursor: pointer;
            transition: all .18s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(196, 154, 108, .07);
            color: var(--text);
        }
        
        .nav-item.active {
            color: var(--caramel);
            border-left-color: var(--caramel);
            background: rgba(196, 154, 108, .1);
            font-weight: 700;
        }
        
        .nav-item .icon {
            font-size: 1rem;
        }
        
        .badge-count {
            margin-left: auto;
            background: var(--marron);
            color: var(--creme);
            font-size: .68rem;
            font-weight: 700;
            padding: .1rem .45rem;
            border-radius: 10px;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 1rem 1.2rem;
            border-top: 1px solid var(--border);
        }
        
        .admin-info {
            display: flex;
            gap: .6rem;
            align-items: center;
        }
        
        .admin-av {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--marron);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        tbody tr:hover {
            background: rgba(196, 154, 108, .06);
        }
        
        .admin-name {
            font-size: .82rem;
            font-weight: 700;
        }
        
        .admin-role {
            font-size: .7rem;
            color: var(--muted);
        }
        /* MAIN */
        
        .main {
            margin-left: 230px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        /* TOP BAR */
        
        .topbar {
            background: var(--sidebar);
            border-bottom: 1px solid var(--border);
            padding: .9rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        
        .topbar h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--caramel);
            font-weight: 700;
        }
        
        .topbar .breadcrumb {
            font-size: .8rem;
            color: var(--muted);
        }
        
        .topbar .breadcrumb span {
            color: var(--caramel);
        }
        
        .topbar-actions {
            margin-left: auto;
            display: flex;
            gap: .8rem;
        }
        
        .btn-add {
            background: var(--vert);
            color: #fff;
            border: none;
            padding: .5rem 1.1rem;
            border-radius: 7px;
            font-size: .84rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
        }
        
        .btn-add:hover {
            background: #1d5230;
        }
        
        .btn-export {
            background: transparent;
            color: var(--caramel);
            border: 1.5px solid var(--caramel);
            padding: .5rem 1.1rem;
            border-radius: 7px;
            font-size: .84rem;
            cursor: pointer;
            transition: all .2s;
        }
        
        .btn-export:hover {
            background: rgba(196, 154, 108, .12);
        }
        /* CONTENT */
        
        .content {
            padding: 1.8rem 2rem;
            flex: 1;
        }
        /* STATS ROW */
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
            transition: transform .2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card .sc-label {
            font-size: .72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        
        .stat-card .sc-num {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            margin: .25rem 0;
        }
        
        .stat-card .sc-num.green {
            color: var(--green);
        }
        
        .stat-card .sc-num.orange {
            color: var(--orange);
        }
        
        .stat-card .sc-num.red {
            color: var(--red);
        }
        
        .stat-card .sc-num.caramel {
            color: var(--caramel);
        }
        
        .stat-card .sc-delta {
            font-size: .75rem;
            color: var(--green);
        }
        /* FILTERS */
        
        .filters {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.4rem;
            flex-wrap: wrap;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .55rem .9rem;
            flex: 1;
            max-width: 320px;
        }
        
        .search-box input {
            background: none;
            border: none;
            color: var(--text);
            font-size: .88rem;
            width: 100%;
            outline: none;
            font-family: 'Lato', sans-serif;
        }
        
        .search-box input::placeholder {
            color: var(--muted);
        }
        
        .filter-select {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            padding: .55rem .9rem;
            border-radius: 8px;
            font-size: .84rem;
            cursor: pointer;
            font-family: 'Lato', sans-serif;
        }
        
        .filter-select:focus {
            outline: 1px solid var(--caramel);
        }
        
        .filter-tabs {
            display: flex;
            gap: 0;
            background: var(--card);
            border-radius: 8px;
            padding: .25rem;
        }
        
        .filter-tab {
            padding: .35rem .9rem;
            border-radius: 6px;
            font-size: .82rem;
            cursor: pointer;
            color: var(--muted);
            border: none;
            background: none;
            font-family: 'Lato', sans-serif;
            transition: all .18s;
        }
        
        .filter-tab.active {
            background: var(--marron);
            color: var(--creme);
            font-weight: 700;
        }
        /* TABLE */
        
        .table-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.1rem 1.4rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-header .th-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--caramel);
        }
        
        .table-header .th-count {
            font-size: .78rem;
            color: var(--muted);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead th {
            text-align: left;
            padding: .8rem 1.2rem;
            font-size: .73rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .07em;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
        }
        
        thead th input[type=checkbox] {
            accent-color: var(--marron);
        }
        
        tbody tr {
            border-bottom: 1px solid rgba(196, 154, 108, .07);
            transition: background .15s;
            cursor: pointer;
        }
        
        tbody tr:last-child {
            border-bottom: none;
        }
        
        tbody tr:hover {
            background: rgba(196, 154, 108, .06);
        }
        
        tbody td {
            padding: .9rem 1.2rem;
            font-size: .86rem;
            vertical-align: middle;
        }
        
        .user-cell {
            display: flex;
            gap: .8rem;
            align-items: center;
        }
        
        .user-av {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            color: var(--creme);
        }
        
        .user-name {
            font-weight: 700;
            font-size: .88rem;
        }
        
        .user-email {
            font-size: .75rem;
            color: var(--muted);
            margin-top: .1rem;
        }
        
        .specialite-cell {
            font-size: .82rem;
            color: var(--caramel);
            font-style: italic;
        }
        
        .competences-cell {
            display: flex;
            gap: .3rem;
            flex-wrap: wrap;
        }
        
        .mini-tag {
            padding: .12rem .5rem;
            border-radius: 10px;
            font-size: .7rem;
            font-weight: 700;
            background: rgba(139, 90, 58, .25);
            color: var(--caramel);
            letter-spacing: .03em;
        }
        
        .mini-tag.green {
            background: rgba(46, 107, 62, .25);
            color: #6fc98c;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .22rem .7rem;
            border-radius: 20px;
            font-size: .74rem;
            font-weight: 700;
        }
        
        .status-badge.actif {
            background: rgba(76, 175, 125, .15);
            color: var(--green);
        }
        
        .status-badge.incomplet {
            background: rgba(240, 160, 80, .15);
            color: var(--orange);
        }
        
        .status-badge.suspendu {
            background: rgba(224, 82, 82, .15);
            color: var(--red);
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .status-dot.actif {
            background: var(--green);
        }
        
        .status-dot.incomplet {
            background: var(--orange);
        }
        
        .status-dot.suspendu {
            background: var(--red);
        }
        
        .progress-bar {
            background: rgba(255, 255, 255, .08);
            border-radius: 4px;
            height: 6px;
            min-width: 80px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--marron), var(--caramel));
        }
        
        .progress-fill.high {
            background: linear-gradient(90deg, var(--vert), #6fc98c);
        }
        
        .actions-cell {
            display: flex;
            gap: .4rem;
            align-items: center;
        }
        
        .btn-icon {
            background: none;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: .3rem .5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: .82rem;
            transition: all .18s;
        }
        
        .btn-icon:hover {
            border-color: var(--caramel);
            color: var(--caramel);
            background: rgba(196, 154, 108, .08);
        }
        
        .btn-icon.danger:hover {
            border-color: var(--red);
            color: var(--red);
            background: rgba(224, 82, 82, .08);
        }
        
        .btn-icon.success:hover {
            border-color: var(--green);
            color: var(--green);
        }
        /* DETAIL PANEL */
        
        .detail-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            z-index: 100;
            display: none;
            justify-content: flex-end;
            backdrop-filter: blur(4px);
        }
        
        .detail-overlay.open {
            display: flex;
        }
        
        .detail-panel {
            width: 440px;
            background: var(--sidebar);
            border-left: 1px solid var(--border);
            height: 100%;
            overflow-y: auto;
            animation: slideIn .3s ease both;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(0);
            }
        }
        
        .dp-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--sidebar);
            z-index: 1;
        }
        
        .dp-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--caramel);
        }
        
        .close-panel {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 1.4rem;
            cursor: pointer;
        }
        
        .close-panel:hover {
            color: var(--text);
        }
        
        .dp-body {
            padding: 1.5rem;
        }
        
        .dp-user-hero {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .dp-av {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            color: var(--creme);
        }
        
        .dp-section {
            margin-bottom: 1.4rem;
        }
        
        .dp-section-title {
            font-size: .72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .7rem;
        }
        
        .dp-field {
            display: flex;
            gap: .8rem;
            margin-bottom: .6rem;
            font-size: .84rem;
        }
        
        .dp-field .lbl {
            color: var(--muted);
            min-width: 100px;
        }
        
        .dp-field .val {
            color: var(--text);
            font-weight: 400;
        }
        
        .dp-bio {
            font-size: .84rem;
            line-height: 1.6;
            color: var(--text);
            background: var(--card);
            border-radius: 8px;
            padding: .8rem 1rem;
            border: 1px solid var(--border);
        }
        
        .dp-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        
        .dp-actions {
            display: flex;
            gap: .8rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border);
        }
        
        .btn-sm-green {
            background: var(--vert);
            color: #fff;
            border: none;
            padding: .45rem 1rem;
            border-radius: 7px;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
        }
        
        .btn-sm-green:hover {
            background: #1d5230;
        }
        
        .btn-sm-orange {
            background: rgba(240, 160, 80, .2);
            color: var(--orange);
            border: 1px solid rgba(240, 160, 80, .3);
            padding: .45rem 1rem;
            border-radius: 7px;
            font-size: .82rem;
            cursor: pointer;
        }
        
        .btn-sm-red {
            background: rgba(224, 82, 82, .15);
            color: var(--red);
            border: 1px solid rgba(224, 82, 82, .25);
            padding: .45rem 1rem;
            border-radius: 7px;
            font-size: .82rem;
            cursor: pointer;
        }
        /* TOAST */
        
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--vert);
            color: #fff;
            padding: .85rem 1.3rem;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 700;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .3);
            transform: translateY(80px);
            opacity: 0;
            transition: all .35s cubic-bezier(.34, 1.56, .64, 1);
            z-index: 999;
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        /* PAGINATION */
        
        .pagination {
            display: flex;
            gap: .5rem;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
            border-top: 1px solid var(--border);
        }
        
        .page-btn {
            background: none;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: .4rem .7rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: .82rem;
            transition: all .15s;
        }
        
        .page-btn:hover {
            border-color: var(--caramel);
            color: var(--caramel);
        }
        
        .page-btn.active {
            background: var(--marron);
            color: var(--creme);
            border-color: var(--marron);
            font-weight: 700;
        }
        
        @media (max-width: 900px) {
            .sidebar {
                width: 60px;
            }
            .nav-item .nav-text,
            .sidebar-logo h1 .sub,
            .nav-label,
            .admin-name,
            .admin-role {
                display: none;
            }
            .main {
                margin-left: 60px;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h1>حرفة <span>Admin</span></h1>
            <div class="sub">Back Office</div>
        </div>
        <nav class="nav-section">
            <div class="nav-label">Tableau de bord</div>
            <a class="nav-item" href="#"><span class="icon">📊</span><span class="nav-text">Dashboard</span></a>
            <div class="nav-label">Modules</div>
            <a class="nav-item active" href="#"><span class="icon">🪪</span><span class="nav-text">Profils Pro</span><span class="badge-count"><?= (int)$stats['total'] ?></span></a>
            <a class="nav-item" href="#"><span class="icon">👥</span><span class="nav-text">Utilisateurs</span></a>
            <a class="nav-item" href="#"><span class="icon">💡</span><span class="nav-text">Projets</span></a>
            <a class="nav-item" href="#"><span class="icon">🎓</span><span class="nav-text">Formations</span></a>
            <a class="nav-item" href="#"><span class="icon">📈</span><span class="nav-text">Investissements</span></a>
            <a class="nav-item" href="#"><span class="icon">📋</span><span class="nav-text">Offres d'emploi</span></a>
            <div class="nav-label">Système</div>
            <a class="nav-item" href="#"><span class="icon">⚡</span><span class="nav-text">Compétences</span></a>
            <a class="nav-item" href="#"><span class="icon">⚙️</span><span class="nav-text">Paramètres</span></a>
            <a class="nav-item" href="#"><span class="icon">📝</span><span class="nav-text">Logs</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-av">👑</div>
                <div>
                    <div class="admin-name">Super Admin</div>
                    <div class="admin-role">CraftLink Tunisie</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <div class="breadcrumb">Back Office · <span>Profils Professionnels</span></div>
                <h2>🪪 Gestion des Profils Professionnels</h2>
            </div>
            <div class="topbar-actions">
                <button class="btn-export" onclick="showToast('📥 Export CSV en cours...')">📥 Exporter</button>
                <button class="btn-add" type="button" onclick="showToast('Fonction non disponible')">+ Nouveau profil</button>
            </div>
        </div>

        <div class="content">
            <?php if ($flashAdmin && !empty($flashAdmin['msg'])): ?>
                <div style="background:<?= $flashAdminClass ?>;color:<?= $flashAdminText ?>;padding:.8rem 1rem;border-radius:.6rem;margin-bottom:1rem;">
                    <?= htmlspecialchars($flashAdmin['msg']) ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg !== ''): ?>
                <div style="background:#ffebee;color:#b71c1c;padding:.8rem 1rem;border-radius:.6rem;margin-bottom:1rem;">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
            <!-- STATS -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="sc-label">Total profils</div>
                    <div class="sc-num caramel"><?= (int)$stats['total'] ?></div>
                    <div class="sc-delta">Total dans la base</div>
                </div>
                <div class="stat-card">
                    <div class="sc-label">Profils actifs</div>
                    <div class="sc-num green"><?= (int)$stats['actif'] ?></div>
                    <div class="sc-delta"><?= $activePercent ?>% du total</div>
                </div>
                <div class="stat-card">
                    <div class="sc-label">Incomplets</div>
                    <div class="sc-num orange"><?= (int)$stats['incomplet'] ?></div>
                    <div class="sc-delta"><?= $incompletePercent ?>% du total</div>
                </div>
                <div class="stat-card">
                    <div class="sc-label">Suspendus</div>
                    <div class="sc-num red"><?= (int)$stats['suspendu'] ?></div>
                    <div class="sc-delta">0 signale</div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="filters">
                <form class="search-box" method="get">
                    <span>🔍</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher par nom, spécialité...">
                    <?php if ($filter !== ''): ?>
                        <input type="hidden" name="statut" value="<?= htmlspecialchars($filter) ?>">
                    <?php endif; ?>
                </form>
                <select class="filter-select" onchange="filterByRole(this.value)">
        <option value="">Tous les rôles</option>
        <option>Artisan</option>
        <option>Mentor</option>
        <option>Entrepreneur</option>
        <option>Investisseur</option>
      </select>
                <select class="filter-select">
        <option>Toutes spécialités</option>
        <option>Céramique</option>
        <option>Tissage</option>
        <option>Broderie</option>
        <option>Zellige</option>
        <option>Cuir</option>
      </select>
                <div class="filter-tabs">
                    <a class="filter-tab<?= $filter === '' ? ' active' : '' ?>" href="<?= $tousHref ?>">Tous (<?= (int)$stats['total'] ?>)</a>
                    <a class="filter-tab<?= $filter === 'actif' ? ' active' : '' ?>" href="admin.php?statut=actif<?= $searchQuery ?>">Actifs (<?= (int)$stats['actif'] ?>)</a>
                    <a class="filter-tab<?= $filter === 'incomplet' ? ' active' : '' ?>" href="admin.php?statut=incomplet<?= $searchQuery ?>">Incomplets (<?= (int)$stats['incomplet'] ?>)</a>
                    <a class="filter-tab<?= $filter === 'suspendu' ? ' active' : '' ?>" href="admin.php?statut=suspendu<?= $searchQuery ?>">Suspendus (<?= (int)$stats['suspendu'] ?>)</a>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-card">
                <div class="table-header">
                    <div class="th-title">Liste des profils professionnels</div>
                    <div class="th-count" id="table-count"><?= count($filteredRows) ?> profils trouvés</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" onchange="toggleAll(this)"></th>
                            <th>Utilisateur</th>
                            <th>Spécialité</th>
                            <th>Compétences</th>
                            <th>Complétion</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="profiles-tbody">
                        <?php if (!empty($filteredRows)): ?>
                            <?php foreach ($filteredRows as $row): ?>
                                <?php
                                    $initial = '';
                                    if (!empty($row['prenom'])) {
                                        $initial = strtoupper(substr($row['prenom'], 0, 1));
                                    } elseif (!empty($row['nom'])) {
                                        $initial = strtoupper(substr($row['nom'], 0, 1));
                                    } else {
                                        $initial = '?';
                                    }
                                    $displayName = trim((string)($row['prenom'] ?? '') . ' ' . (string)($row['nom'] ?? ''));
                                    $displayDate = '';
                                    if (!empty($row['date_creation'])) {
                                        $displayDate = date('d M Y', strtotime($row['date_creation']));
                                    }
                                    $compList = $row['competence_list'] ?? [];
                                    $compCount = count($compList);
                                ?>
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-av" style="background:<?= htmlspecialchars($row['color']) ?>"><?= htmlspecialchars($initial) ?></div>
                                            <div>
                                                <div class="user-name"><?= htmlspecialchars($displayName) ?></div>
                                                <div class="user-email"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="specialite-cell"><?= htmlspecialchars($row['specialite'] ?? '') ?></td>
                                    <td>
                                        <div class="competences-cell">
                                            <?php if ($compCount > 0): ?>
                                                <?php foreach (array_slice($compList, 0, 2) as $idx => $comp): ?>
                                                    <span class="mini-tag <?= $idx % 2 === 1 ? 'green' : '' ?>"><?= htmlspecialchars($comp) ?></span>
                                                <?php endforeach; ?>
                                                <?php if ($compCount > 2): ?>
                                                    <span class="mini-tag">+<?= $compCount - 2 ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="mini-tag">Aucune</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:.5rem;">
                                            <div class="progress-bar">
                                                <div class="progress-fill <?= $row['completion'] >= 80 ? 'high' : '' ?>" style="width:<?= (int)$row['completion'] ?>%"></div>
                                            </div>
                                            <span style="font-size:.75rem;color:var(--muted)"><?= (int)$row['completion'] ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= htmlspecialchars($row['statut']) ?>">
                                            <span class="status-dot <?= htmlspecialchars($row['statut']) ?>"></span>
                                            <?= htmlspecialchars(ucfirst($row['statut'])) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($displayDate) ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a class="btn-icon" title="Voir" href="profil.php?id=<?= (int)$row['id_user'] ?>">👁</a>
                                            <button class="btn-icon" type="button" onclick="showToast('Edition non disponible')">✏️</button>
                                            <button class="btn-icon success" type="button" onclick="showToast('Profil valide')">✅</button>
                                            <form action="delete_user.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                                <input type="hidden" name="id" value="<?= (int)$row['id_user'] ?>">
                                                <button class="btn-icon danger" type="submit" title="Supprimer">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; padding:1rem;">Aucun profil</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <button class="page-btn">‹</button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">›</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAIL PANEL -->
    <div class="detail-overlay" id="detail-overlay" onclick="closeDetailOutside(event)">
        <div class="detail-panel" id="detail-panel">
            <div class="dp-header">
                <h3 id="dp-title">Détail du profil</h3>
                <button class="close-panel" onclick="closeDetail()">✕</button>
            </div>
            <div class="dp-body" id="dp-body"></div>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast">✅ Action effectuée</div>

        <script>
        function toggleAll(cb) {
                document.querySelectorAll('#profiles-tbody input[type=checkbox]').forEach(function(c) {
                        c.checked = cb.checked;
                });
        }

        function showToast(msg) {
                var t = document.getElementById('toast');
                if (!t) {
                        return;
                }
                t.textContent = msg;
                t.classList.add('show');
                setTimeout(function() {
                        t.classList.remove('show');
                }, 3000);
        }

        function openDetail() {
                var overlay = document.getElementById('detail-overlay');
                if (overlay) {
                        overlay.classList.add('open');
                }
        }

        function closeDetail() {
                var overlay = document.getElementById('detail-overlay');
                if (overlay) {
                        overlay.classList.remove('open');
                }
        }

        function closeDetailOutside(e) {
                var overlay = document.getElementById('detail-overlay');
                if (overlay && e.target === overlay) {
                        closeDetail();
                }
        }
        </script>
</body>

</html>