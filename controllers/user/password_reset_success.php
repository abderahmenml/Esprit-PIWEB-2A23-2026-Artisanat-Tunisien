<?php
session_start();
unset($_SESSION['reset_email_sent'], $_SESSION['reset_verified_email']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe modifié</title>
  <meta http-equiv="refresh" content="3;url=/herfa/public/frontend/login.html">
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
    <div class="icon">✅</div>
    <h1>Mot de passe modifié</h1>
    <p>Votre mot de passe a été changé avec succès. Redirection vers la page de connexion...</p>
    <a class="btn" href="/herfa/public/frontend/login.html">Aller à la connexion</a>
  </div>
</body>
</html>
