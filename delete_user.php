<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash_admin'] = ['type' => 'error', 'msg' => 'ID invalide.'];
    header('Location: admin.php');
    exit;
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
        header('Location: admin.php');
        exit;
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

header('Location: admin.php');
exit;
