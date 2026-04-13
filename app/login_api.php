<?php
header("Content-Type: application/json");
session_start();
require "database.php";

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$role     = trim($_POST['role']     ?? '');

// ── Champs manquants ──
if (!$email || !$password || !$role) {
    echo json_encode([
        "success" => false,
        "field"   => "general",
        "message" => "Veuillez remplir tous les champs."
    ]);
    exit;
}

// ── Chercher l'utilisateur par email ──
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Email introuvable ──
if (!$user) {
    echo json_encode([
        "success" => false,
        "field"   => "email",
        "message" => "Aucun compte trouvé avec cette adresse e-mail."
    ]);
    exit;
}

// ── Mot de passe incorrect ──
if (!password_verify($password, $user['mot_de_passe'])) {
    echo json_encode([
        "success" => false,
        "field"   => "password",
        "message" => "Mot de passe incorrect."
    ]);
    exit;
}

// ── Rôle incorrect ──
if ($user['role'] !== $role) {
    echo json_encode([
        "success" => false,
        "field"   => "role",
        "message" => "Rôle incorrect. Ce compte est enregistré en tant que « " . ucfirst($user['role']) . " »."
    ]);
    exit;
}

// ── Compte inactif ──
if ($user['etat_compte'] !== 'actif') {
    echo json_encode([
        "success" => false,
        "field"   => "general",
        "message" => "Votre compte est inactif. Contactez l'administrateur."
    ]);
    exit;
}

// ── ✅ Connexion réussie ──
$_SESSION['user_id'] = $user['id_user'];
$_SESSION['nom']     = $user['nom'];
$_SESSION['prenom']  = $user['prenom'];
$_SESSION['role']    = $user['role'];
$_SESSION['email']   = $user['email'];

echo json_encode([
    "success" => true,
    "nom"     => $user['nom'],
    "prenom"  => $user['prenom'],
    "role"    => $user['role']
]);
?>