<?php
// save_profile.php
// Insert or update profil_profetionnel for logged-in user

declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit_profile.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$specialite = trim($_POST['specialite'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$portfolio = trim($_POST['portfolio'] ?? '');

if ($specialite === '' || $bio === '' || $experience === '') {
    header('Location: edit_profile.php');
    exit();
}

// Bonus: profile image upload, append path to portfolio if provided
if (isset($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $tmp = $_FILES['profile_image']['tmp_name'];
    $name = basename((string)$_FILES['profile_image']['name']);
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: ('profile_' . time() . '.jpg');
    $target = __DIR__ . '/uploads/profiles/' . $safeName;
    if (move_uploaded_file($tmp, $target)) {
        $uploadedPath = 'uploads/profiles/' . $safeName;
        $portfolio = trim($portfolio . ' ' . $uploadedPath);
    }
}

$checkStmt = $pdo->prepare('SELECT id_profil FROM profil_profetionnel WHERE id_user = ? LIMIT 1');
$checkStmt->execute([$userId]);
$existing = $checkStmt->fetch();

if ($existing) {
    $updateSql = 'UPDATE profil_profetionnel
                  SET `specialité` = ?, bio = ?, experience = ?, portfolio = ?
                  WHERE id_user = ?';
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$specialite, $bio, $experience, $portfolio, $userId]);
} else {
    $insertSql = 'INSERT INTO profil_profetionnel (id_user, `specialité`, bio, experience, portfolio, date_creation)
                  VALUES (?, ?, ?, ?, ?, NOW())';
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$userId, $specialite, $bio, $experience, $portfolio]);
}

header('Location: profile.php');
exit();
