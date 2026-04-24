<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérification du compte - CraftLink</title>
  <?php if ($success): ?>
  <meta http-equiv="refresh" content="4;url=index.php?page=login">
  <?php endif; ?>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Calibri', sans-serif; background: #F5ECD7; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .box { background: white; padding: 2.5rem; border-radius: 20px; max-width: 520px; width: 90%; box-shadow: 0 10px 40px rgba(59,35,20,.12); text-align: center; animation: popIn .5s ease both; }
    .icon { font-size: 4rem; margin-bottom: 1rem; }
    h1   { font-family: Georgia, serif; color: #3B2314; margin-bottom: .8rem; font-size: 1.6rem; }
    p    { color: #5C3A1E; line-height: 1.7; margin-bottom: 1.2rem; }
    .btn { display: inline-block; margin-top: .5rem; padding: .85rem 1.6rem; background: #3B2314; color: #F5ECD7; text-decoration: none; border-radius: 10px; font-family: Georgia, serif; font-weight: bold; transition: background .2s; }
    .btn:hover { background: #5C3A1E; }
    .note { font-size: .8rem; color: #A08060; margin-top: 1rem; }
    @keyframes popIn { from { opacity: 0; transform: scale(.92) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
  </style>
</head>
<body>
  <div class="box">
    <div class="icon"><?= $success ? '✅' : '⚠️' ?></div>
    <h1><?= $success ? 'Compte activé !' : 'Vérification impossible' ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <a class="btn" href="index.php?page=login">
      <?= $success ? '🚀 Aller à la connexion' : '← Retour' ?>
    </a>
    <?php if ($success): ?>
    <p class="note">Redirection automatique dans 4 secondes…</p>
    <?php endif; ?>
  </div>
</body>
</html>
