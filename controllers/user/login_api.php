<?php
header("Content-Type: application/json");
session_start();
require "database.php";

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$role     = mb_strtolower(trim($_POST['role'] ?? ''), 'UTF-8');

// Compatibility mapping for existing UI labels
$role = match ($role) {
    'mentor' => 'artisan',
    'investisseur' => 'entrepreneur',
    default => $role,
};

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
if (mb_strtolower((string)$user['role'], 'UTF-8') !== $role) {
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
// Normalize role to lowercase for consistent checks in PHP and JS
$normalizedRole = mb_strtolower((string)$user['role'], 'UTF-8');
$_SESSION['role']    = $normalizedRole;
$_SESSION['email']   = $user['email'];
// Determine if onboarding should be shown for artisan role
$onboardingRequired = false;
if (mb_strtolower((string)$user['role'], 'UTF-8') === 'artisan') {
    try {
        $stmt = $pdo->prepare('SELECT completed_at FROM artisan_onboarding WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user['id_user']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // If no row or completed_at is null -> onboarding needed
        // NOTE: ignore any session skip flag here so users with incomplete onboarding always see it
        if (!$row || empty($row['completed_at'])) {
            $onboardingRequired = true;
        }
    } catch (Throwable $e) {
        // Fail silently — do not block login
        $onboardingRequired = false;
    }
    // Debug helpers (helpful during local dev) — expose whether onboarding row exists and its completed_at
    $onboardingRowExists = isset($row) && $row !== false;
    $onboardingCompletedAt = $onboardingRowExists ? ($row['completed_at'] ?? null) : null;
}

echo json_encode([
    "success" => true,
    "nom"     => $user['nom'],
    "prenom"  => $user['prenom'],
    "role"    => $normalizedRole,
    "onboarding" => $onboardingRequired
    , "debug_onboarding_row_exists" => $onboardingRowExists ?? false
    , "debug_onboarding_completed_at" => $onboardingCompletedAt ?? null
]);
?>