<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe modifié - CraftLink</title>
  <meta http-equiv="refresh" content="3;url=index.php?page=login">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Calibri', sans-serif; background: #F5ECD7; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .box { background: white; padding: 2.5rem; border-radius: 20px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(59,35,20,.12); text-align: center; animation: popIn .5s ease both; }
    .icon { font-size: 4rem; margin-bottom: 1rem; }
    h1   { font-family: Georgia, serif; color: #3B2314; margin-bottom: .8rem; }
    p    { color: #5C3A1E; line-height: 1.7; }
    .btn { display: inline-block; margin-top: 1.2rem; padding: .85rem 1.6rem; background: #3B2314; color: #F5ECD7; text-decoration: none; border-radius: 10px; font-family: Georgia, serif; font-weight: bold; }
    .btn:hover { background: #5C3A1E; }
    .note { font-size: .8rem; color: #A08060; margin-top: .8rem; }
    @keyframes popIn { from { opacity: 0; transform: scale(.92) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
  </style>
</head>
<body>
  <div class="box">
    <div class="icon">✅</div>
    <h1>Mot de passe modifié</h1>
    <p>Votre mot de passe a été changé avec succès.<br>Redirection vers la connexion…</p>
    <a class="btn" href="index.php?page=login">Aller à la connexion</a>
    <p class="note">Redirection automatique dans 3 secondes.</p>
  </div>
</body>
</html>
