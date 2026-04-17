<?php
include '../../config.php';

$error = '';
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'])) {
      $name = trim($_POST['name']);
    }

    if ($name === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $db = config::getConnexion();
      $query = $db->prepare('SELECT id_user FROM user WHERE nom = :nom');
      $query->execute(['nom' => $name]);
        $user = $query->fetch();

      if ($user) {
            $_SESSION['id_user'] = (int)$user['id_user'];
            header('Location: index.php');
            exit;
        }

        $error = 'Nom ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="grain"></div>
  <header class="topbar">
    <div class="brand-block">
      <div class="brand-mark">
        <img src="assets/images/logo.png" alt="Logo 7erfa Tunisie">
      </div>
      <div>
        <p class="brand-name">7erfa Tunisie</p>
        <p class="brand-tagline">L'artisanat tunisien, a l'ere du numerique.</p>
      </div>
    </div>
    <a class="cta-outline" href="index.php">Retour</a>
  </header>

  <main class="layout">
    <section class="card-surface" style="padding: 1.5rem; max-width: 520px; margin: 0 auto;">
      <h2 style="margin-bottom: 0.6rem;">Connexion</h2>
      <p style="margin-bottom: 1.2rem; color: #6b5a49;">Entrez votre nom.</p>

      <?php if ($error !== ''): ?>
        <p style="color: #c03a3d; font-weight: 700; margin-bottom: 0.8rem;">
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </p>
      <?php endif; ?>

      <form method="post" action="login.php" class="idea-form" style="grid-template-columns: 1fr;">
        <label class="wide">
          Nom
          <input type="text" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>

        <button class="cta-solid wide" type="submit">Se connecter</button>
      </form>
    </section>
  </main>
</body>
</html>
