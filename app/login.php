<?php
// login.php
// Authenticate against table `user` and redirect to home.php dashboard

declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    app_redirect('home.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('login.html');
}

$email = trim($_POST['email'] ?? '');
$motDePasse = $_POST['mot_de_passe'] ?? '';

if ($email === '' || $motDePasse === '') {
    app_redirect('login.html?error=missing');
}

$sql = 'SELECT id_user, role, mot_de_passe, etat_compte, nom, prenom FROM `user` WHERE email = ? LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    app_redirect('login.html?error=user');
}

if ((string)$user['etat_compte'] !== 'actif') {
    app_redirect('login.html?error=inactive');
}

$storedPassword = (string)$user['mot_de_passe'];
$passwordOk = password_verify($motDePasse, $storedPassword) || hash_equals($storedPassword, $motDePasse);

if (!$passwordOk) {
    app_redirect('login.html?error=password');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id_user'];
$_SESSION['role'] = (string)$user['role'];
$_SESSION['nom'] = (string)$user['nom'];
$_SESSION['prenom'] = (string)$user['prenom'];

// Redirect all authenticated users to home.php dashboard
app_redirect('home.php');
