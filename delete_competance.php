<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profil_professionnel.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'ID invalide.'];
    header('Location: profil_professionnel.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM competences WHERE id_competence = ? AND id_user = ?');
    $stmt->execute([$id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Competence supprimee.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Suppression impossible.'];
    }
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erreur lors de la suppression.'];
}

header('Location: profil_professionnel.php');
exit;