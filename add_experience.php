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
$poste = trim((string)($_POST['poste'] ?? ''));
$entreprise = trim((string)($_POST['entreprise'] ?? ''));
$date_debut = trim((string)($_POST['date_debut'] ?? ''));
$date_fin = trim((string)($_POST['date_fin'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if ($poste === '' || $date_debut === '') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Champs invalides.'];
    header('Location: profil_professionnel.php');
    exit;
}

if (strtotime($date_debut) === false) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Date de debut invalide.'];
    header('Location: profil_professionnel.php');
    exit;
}

if ($date_fin !== '' && strtotime($date_fin) === false) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Date de fin invalide.'];
    header('Location: profil_professionnel.php');
    exit;
}

if ($date_fin === '') {
    $date_fin = null;
}

try {
    $pdo = getPDO();
    $check = $pdo->query("SHOW TABLES LIKE 'experience'");
    if ($check->rowCount() == 0) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Table experience introuvable.'];
        header('Location: profil_professionnel.php');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO experience (id_user, poste, entreprise, date_debut, date_fin, description) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $poste, $entreprise, $date_debut, $date_fin, $description]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Experience ajoutee.'];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erreur lors de l\'ajout.'];
}

header('Location: profil_professionnel.php');
exit;
