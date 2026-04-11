<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require 'db.php';
require 'mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier que PHPMailer existe
$phpMailerFiles = [
    __DIR__ . '/src/Exception.php',
    __DIR__ . '/src/PHPMailer.php',
    __DIR__ . '/src/SMTP.php'
];

foreach ($phpMailerFiles as $file) {
    if (!file_exists($file)) {
        echo json_encode([
            'success' => false,
            'message' => 'PHPMailer est introuvable. Vérifiez le dossier src dans htdocs/craftlink/.'
        ]);
        exit;
    }
}

require __DIR__ . '/src/Exception.php';
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';

// Récupération des données
$nom      = trim($_POST['nom'] ?? '');
$prenom   = trim($_POST['prenom'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = trim($_POST['role'] ?? 'entrepreneur');

// Validation
if ($nom === '' || $prenom === '' || $email === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs sont obligatoires.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Adresse e-mail invalide.'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Le mot de passe doit contenir au moins 6 caractères.'
    ]);
    exit;
}

$rolesAutorises = ['entrepreneur', 'artisan', 'mentor', 'investisseur'];
if (!in_array($role, $rolesAutorises, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Rôle invalide.'
    ]);
    exit;
}

try {
    // Vérifier si email déjà dans la table user
    $checkUser = $pdo->prepare("SELECT id_user FROM user WHERE email = ?");
    $checkUser->execute([$email]);

    if ($checkUser->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Cet email est déjà utilisé.'
        ]);
        exit;
    }

    // Créer table pending_users si elle n'existe pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pending_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $token = bin2hex(random_bytes(32));
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

    // Insérer ou mettre à jour dans pending_users
    $upsert = $pdo->prepare("
        INSERT INTO pending_users (nom, prenom, email, mot_de_passe, role, token, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            nom = VALUES(nom),
            prenom = VALUES(prenom),
            mot_de_passe = VALUES(mot_de_passe),
            role = VALUES(role),
            token = VALUES(token),
            expires_at = VALUES(expires_at)
    ");
    $upsert->execute([$nom, $prenom, $email, $hash, $role, $token, $expiresAt]);

    $verifyLink = APP_BASE_URL . '/verify.php?token=' . urlencode($token);
    $pageName = 'CraftLink Tunisie';

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($email, $prenom . ' ' . $nom);

    $mail->isHTML(true);
    $mail->Subject = 'Vérification de votre compte - ' . $pageName;
    $mail->Body = '
        <h2>Bienvenue sur ' . htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8') . '</h2>
        <p>Bonjour ' . htmlspecialchars($prenom . ' ' . $nom, ENT_QUOTES, 'UTF-8') . ',</p>
        <p>Pour autoriser la création de votre compte, cliquez sur le bouton ci-dessous :</p>
        <p>
            <a href="' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '" 
               style="display:inline-block;padding:12px 20px;background:#2E6B3E;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:bold;">
               Autoriser
            </a>
        </p>
        <p>Ou copiez ce lien dans votre navigateur :</p>
        <p>' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '</p>
        <p>Ce lien expire dans 24 heures.</p>
    ';
    $mail->AltBody = "Bienvenue sur $pageName\n\nAutorisez la création de votre compte ici : $verifyLink\n\nCe lien expire dans 24 heures.";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => "Un mail de vérification a été envoyé à $email. Ouvrez Gmail et cliquez sur Autoriser."
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Le mail n'a pas pu être envoyé. Vérifiez la configuration SMTP Gmail. Détail : " . $e->getMessage()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données : ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
?>