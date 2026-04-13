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
$nom = trim((string)($_POST['nom'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

if ($nom === '' || $niveau === null || $niveau === false || $niveau < 0 || $niveau > 100) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Champs invalides.'];
    header('Location: profil_professionnel.php');
    exit;
}

try {
    $pdo = getPDO();
    $check = $pdo->query("SHOW TABLES LIKE 'competences'");
    if ($check->rowCount() == 0) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Table competences introuvable.'];
        header('Location: profil_professionnel.php');
        exit;
    }

    $stmtOrdre = $pdo->prepare('SELECT COALESCE(MAX(ordre), 0) + 1 FROM competences WHERE id_user = ?');
    $stmtOrdre->execute([$user_id]);
    $nextOrdre = (int)$stmtOrdre->fetchColumn();
    if ($nextOrdre <= 0) {
        $nextOrdre = 1;
    }

    $stmt = $pdo->prepare('INSERT INTO competences (id_user, nom_competence, description, niveau, ordre) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $nom, $description, $niveau, $nextOrdre]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Competence ajoutee.'];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erreur lors de l\'ajout.'];
}

header('Location: profil_professionnel.php');
exit;