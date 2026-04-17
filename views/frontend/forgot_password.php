<?php
session_start();
require dirname(__DIR__, 2) . '/config/Config.php';
require dirname(__DIR__, 2) . '/app/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$phpMailerFiles = [
  dirname(__DIR__, 2) . '/controllers/src/Exception.php',
  dirname(__DIR__, 2) . '/controllers/src/PHPMailer.php',
  dirname(__DIR__, 2) . '/controllers/src/SMTP.php'
];

foreach ($phpMailerFiles as $file) {
    if (!file_exists($file)) {
        die('PHPMailer est introuvable. Vérifiez le dossier src dans htdocs/craftlink/.');
    }
}

require dirname(__DIR__, 2) . '/controllers/src/Exception.php';
require dirname(__DIR__, 2) . '/controllers/src/PHPMailer.php';
require dirname(__DIR__, 2) . '/controllers/src/SMTP.php';

$success = '';
$error = '';
$step = 'email';
$emailValue = '';


if (isset($_GET['restart'])) {
    unset($_SESSION['reset_email_sent'], $_SESSION['reset_verified_email']);
    header('Location: forgot_password.php');
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

if (isset($_SESSION['reset_email_sent'])) {
    $step = 'code';
    $emailValue = $_SESSION['reset_email_sent'];
}

if (isset($_SESSION['reset_verified_email'])) {
    $step = 'password';
    $emailValue = $_SESSION['reset_verified_email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_code') {
        $email = trim($_POST['email'] ?? '');
        $emailValue = $email;

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Veuillez entrer une adresse e-mail valide.';
            $step = 'email';
        } else {
            $stmt = $pdo->prepare("SELECT id_user, prenom, nom FROM user WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Aucun compte trouvé avec cette adresse e-mail.';
                $step = 'email';
            } else {
                try {
                    $code = (string) random_int(100000, 999999);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    $upsert = $pdo->prepare("INSERT INTO password_resets (email, code, expires_at, verified)
                        VALUES (?, ?, ?, 0)
                        ON DUPLICATE KEY UPDATE
                            code = VALUES(code),
                            expires_at = VALUES(expires_at),
                            verified = 0,
                            created_at = CURRENT_TIMESTAMP");
                    $upsert->execute([$email, $code, $expiresAt]);

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
                    $mail->addAddress($email, trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')));
                    $mail->isHTML(true);
                    $mail->Subject = 'Code de vérification - Réinitialisation du mot de passe';
                    $mail->Body = '
                        <div style="font-family:Arial,sans-serif;line-height:1.6;color:#3B2314">
                            <h2>CraftLink Tunisie</h2>
                            <p>Bonjour ' . htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')), ENT_QUOTES, 'UTF-8') . ',</p>
                            <p>Voici votre code de vérification pour réinitialiser votre mot de passe :</p>
                            <div style="font-size:32px;font-weight:bold;letter-spacing:8px;background:#F5ECD7;padding:14px 18px;border-radius:10px;display:inline-block;color:#2E6B3E;">
                                ' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '
                            </div>
                            <p style="margin-top:20px;">Ce code expire dans <strong>15 minutes</strong>.</p>
                        </div>';
                    $mail->AltBody = "Votre code de vérification est : $code. Ce code expire dans 15 minutes.";
                    $mail->send();

                    $_SESSION['reset_email_sent'] = $email;
                    unset($_SESSION['reset_verified_email']);
                    $success = 'Un code de vérification a été envoyé à votre adresse e-mail.';
                    $step = 'code';
                } catch (Throwable $e) {
                    $error = "Le code n'a pas pu être envoyé. Vérifiez la configuration SMTP Gmail.";
                    $step = 'email';
                }
            }
        }
    }

    if ($action === 'verify_code') {
        $email = trim($_SESSION['reset_email_sent'] ?? '');
        $code  = trim($_POST['code'] ?? '');
        $emailValue = $email;

        if ($email === '') {
            $error = 'Veuillez recommencer depuis le début.';
            $step = 'email';
        } elseif ($code === '') {
            $error = 'Veuillez entrer le code de vérification.';
            $step = 'code';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $error = 'Aucune demande de réinitialisation trouvée.';
                $step = 'email';
            } elseif (strtotime($reset['expires_at']) < time()) {
                $error = 'Le code a expiré. Demandez un nouveau code.';
                unset($_SESSION['reset_email_sent'], $_SESSION['reset_verified_email']);
                $step = 'email';
            } elseif ($reset['code'] !== $code) {
                $error = 'Code incorrect. Vérifiez le code reçu par e-mail.';
                $step = 'code';
            } else {
                $verify = $pdo->prepare("UPDATE password_resets SET verified = 1 WHERE email = ?");
                $verify->execute([$email]);

                $_SESSION['reset_verified_email'] = $email;
                $success = 'Code correct. Vous pouvez maintenant changer votre mot de passe.';
                $step = 'password';
            }
        }
    }

    if ($action === 'change_password') {
        $email = trim($_SESSION['reset_verified_email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $emailValue = $email;

        if ($email === '') {
            $error = 'Session expirée. Veuillez recommencer.';
            $step = 'email';
        } elseif ($newPassword === '' || $confirmPassword === '') {
            $error = 'Veuillez remplir les deux champs mot de passe.';
            $step = 'password';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
            $step = 'password';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les mots de passe ne correspondent pas.';
            $step = 'password';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND verified = 1 LIMIT 1");
            $stmt->execute([$email]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $error = 'Vérification invalide. Veuillez recommencer.';
                $step = 'email';
            } elseif (strtotime($reset['expires_at']) < time()) {
                $error = 'Votre code a expiré. Veuillez recommencer.';
                unset($_SESSION['reset_email_sent'], $_SESSION['reset_verified_email']);
                $step = 'email';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE user SET mot_de_passe = ? WHERE email = ?");
                $update->execute([$hash, $email]);

                $delete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $delete->execute([$email]);

                unset($_SESSION['reset_email_sent'], $_SESSION['reset_verified_email']);
                header('Location: password_reset_success.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié - CraftLink Tunisie</title>
  <link rel="stylesheet" href="/herfa/public/assets/css/login_register.css">
  <style>
    body { overflow: auto; }
    .reset-wrapper { min-height: 100vh; display:flex; width:100%; }
    .reset-card { width:100%; }
    .step-badges { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    .step-badge { padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; background:rgba(139,90,58,.12); color:#5C3A1E; }
    .step-badge.active { background:#3B2314; color:#F5ECD7; }
    .helper-text { font-size:.82rem; color:var(--text-muted); margin:-6px 0 16px; line-height:1.6; }
    .back-link { display:inline-block; margin-top:16px; color:var(--vert); text-decoration:none; font-weight:600; }
    .back-link:hover { color:var(--marron); }
  </style>
</head>
<body>
<div class="reset-wrapper">
  <div class="left-panel">
    <div class="brand">
      <div class="logo-ring">
        <img src="/herfa/public/assets/img/logo_herfa.png" alt="CraftLink Logo" class="logo-img">
      </div>
      <div class="brand-title">CraftLink Tunisie</div>
      <div class="brand-arabic">حرفة تونس</div>
      <div class="brand-sub">L'artisanat tunisien, à l'ère du numérique.</div>
    </div>

    <div class="divider-ornament"><div class="divider-diamond"></div></div>

    <div class="roles-title">Réinitialisation sécurisée</div>
    <div class="roles">
      <div class="role-card entrepreneur">✉ E-mail</div>
      <div class="role-card mentor">🔐 Code</div>
      <div class="role-card investisseur">🔑 Nouveau mot de passe</div>
    </div>

    <p class="left-quote">« Entrez votre adresse e-mail, recevez votre code, puis choisissez un nouveau mot de passe. »</p>
  </div>

  <div class="right-panel reset-card">
    <div class="form-header">
      <div class="form-label-small">Mot de passe oublié</div>
      <div class="form-title">Réinitialiser votre mot de passe</div>
      <div class="form-subtitle">Suivez les 3 étapes pour récupérer l'accès à votre compte.</div>
    </div>

    <div class="step-badges">
      <span class="step-badge <?php echo $step === 'email' ? 'active' : ''; ?>">1. E-mail</span>
      <span class="step-badge <?php echo $step === 'code' ? 'active' : ''; ?>">2. Code</span>
      <span class="step-badge <?php echo $step === 'password' ? 'active' : ''; ?>">3. Nouveau mot de passe</span>
    </div>

    <?php if ($success): ?>
      <div class="alert-banner alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-banner alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($step === 'email'): ?>
      <form method="post">
        <input type="hidden" name="action" value="send_code">
        <div class="field-group">
          <label class="field-label" for="email">Adresse e-mail</label>
          <div class="field-wrap">
            <span class="field-icon">✉</span>
            <input type="email" id="email" name="email" placeholder="votre@email.com" value="<?php echo htmlspecialchars($emailValue); ?>" required>
          </div>
        </div>
        <p class="helper-text">Nous allons envoyer un code de vérification à votre adresse e-mail.</p>
        <button class="btn-login" type="submit">Envoyer le code →</button>
      </form>
    <?php endif; ?>

    <?php if ($step === 'code'): ?>
      <form method="post">
        <input type="hidden" name="action" value="verify_code">
        <div class="field-group">
          <label class="field-label" for="code">Code de vérification</label>
          <div class="field-wrap">
            <span class="field-icon">🔐</span>
            <input type="text" id="code" name="code" placeholder="Entrez le code reçu" maxlength="6" required>
          </div>
        </div>
        <p class="helper-text">Un code a été envoyé à <strong><?php echo htmlspecialchars($emailValue); ?></strong>.</p>
        <button class="btn-login" type="submit">Vérifier le code →</button>
      </form>
      <a class="back-link" href="forgot_password.php?restart=1">← Changer l'adresse e-mail</a>
    <?php endif; ?>

    <?php if ($step === 'password'): ?>
      <form method="post">
        <input type="hidden" name="action" value="change_password">
        <div class="field-group">
          <label class="field-label" for="new_password">Nouveau mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" id="new_password" name="new_password" placeholder="••••••••" required>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label" for="confirm_password">Confirmer le mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
          </div>
        </div>
        <p class="helper-text">Après validation, votre mot de passe sera mis à jour dans la base de données, puis vous serez redirigé vers la page de connexion.</p>
        <button class="btn-login" type="submit">Changer le mot de passe →</button>
      </form>
    <?php endif; ?>

    <a class="back-link" href="/herfa/public/frontend/login.html">← Retour à la connexion</a>
  </div>
</div>
</body>
</html>
