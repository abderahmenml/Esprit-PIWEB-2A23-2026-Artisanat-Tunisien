<?php
require 'database.php';

$token = trim($_GET['token'] ?? '');
$message = '';
$success = false;

if (!$token) {
    $message = "Lien invalide.";
} else {
    $stmt = $pdo->prepare("SELECT * FROM pending_users WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pending) {
        $message = "Lien de vérification introuvable ou déjà utilisé.";
    } elseif (strtotime($pending['expires_at']) < time()) {
        $message = "Ce lien a expiré.";
    } else {
        $exists = $pdo->prepare("SELECT id_user FROM user WHERE email = ? LIMIT 1");
        $exists->execute([$pending['email']]);

        if ($exists->fetch()) {
            $message = "Cet utilisateur existe déjà.";
        } else {
            $insert = $pdo->prepare("INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $pending['nom'],
                $pending['prenom'],
                $pending['email'],
                $pending['mot_de_passe'],
                $pending['role'],
                date('Y-m-d'),
                'actif'
            ]);

            $delete = $pdo->prepare("DELETE FROM pending_users WHERE id = ?");
            $delete->execute([$pending['id']]);

            $success = true;
            $message = "Utilisateur créé avec succès.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérification du compte</title>
  <style>
    body { font-family: Arial, sans-serif; background:#F5ECD7; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
    .box { background:white; padding:32px; border-radius:16px; max-width:520px; width:90%; box-shadow:0 10px 30px rgba(0,0,0,.12); text-align:center; }
    h1 { color:#3B2314; margin-bottom:12px; }
    p { color:#5C3A1E; line-height:1.6; }
    .icon { font-size:54px; margin-bottom:12px; }
    .btn { display:inline-block; margin-top:18px; padding:12px 18px; background:#3B2314; color:#fff; text-decoration:none; border-radius:8px; }
  </style>
</head>
<body>
  <div class="box">
    <div class="icon"><?php echo $success ? '✅' : '⚠️'; ?></div>
    <h1><?php echo $success ? 'Compte vérifié' : 'Vérification impossible'; ?></h1>
    <p><?php echo htmlspecialchars($message); ?></p>
    <a class="btn" href="/herfa/public/frontend/login.html"><?php echo $success ? 'Aller à la connexion' : 'Retour'; ?></a>
  </div>
</body>
</html>
