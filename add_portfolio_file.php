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

if (!isset($_FILES['portfolio_file']) || $_FILES['portfolio_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Fichier invalide.'];
    header('Location: profil_professionnel.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$titre = trim((string)($_POST['titre'] ?? ''));
$originalName = $_FILES['portfolio_file']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($ext !== 'pdf') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Seuls les fichiers PDF sont autorises.'];
    header('Location: profil_professionnel.php');
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolio';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
if ($baseName === '') {
    $baseName = 'document';
}

$storedName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

if (!move_uploaded_file($_FILES['portfolio_file']['tmp_name'], $targetPath)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Echec du televersement.'];
    header('Location: profil_professionnel.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO portfolio_files (id_user, titre, file_name, file_path, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([
        $user_id,
        $titre === '' ? null : $titre,
        $originalName,
        'uploads/portfolio/' . $storedName
    ]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Document ajoute.'];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erreur lors de l\'enregistrement.'];
}

header('Location: profil_professionnel.php');
exit;
