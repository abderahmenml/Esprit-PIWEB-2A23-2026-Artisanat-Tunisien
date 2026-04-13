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
  <title>Vérification du compte | حرفة Tunisie</title>
  <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
</head>
<body>
  <header>
    <h1 class="site-heading text-center text-faded d-none d-lg-block">
      <span class="site-heading-upper text-primary mb-3">Validation du compte</span>
      <span class="site-heading-lower">حرفة Tunisie</span>
    </h1>
  </header>
  <nav class="navbar navbar-expand-lg navbar-dark py-lg-4" id="mainNav">
    <div class="container">
      <a class="navbar-brand text-uppercase fw-bold d-lg-none" href="index.html">حرفة Tunisie</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item px-lg-4"><a class="nav-link text-uppercase" href="index.html">Accueil</a></li>
          <li class="nav-item px-lg-4"><a class="nav-link text-uppercase" href="about.html">Vision</a></li>
          <li class="nav-item px-lg-4"><a class="nav-link text-uppercase" href="login.html">Connexion</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="page-section cta">
    <div class="container">
      <div class="cta-inner bg-faded rounded p-5 text-center">
        <div class="display-5 mb-3"><?php echo $success ? '✅' : '⚠️'; ?></div>
        <h2 class="mb-3"><?php echo $success ? 'Compte vérifié' : 'Vérification impossible'; ?></h2>
        <p class="text-muted mb-4"><?php echo htmlspecialchars($message); ?></p>
        <a class="btn btn-primary btn-xl" href="login.html"><?php echo $success ? 'Aller à la connexion' : 'Retour'; ?></a>
      </div>
    </div>
  </section>

  <footer class="footer text-faded text-center py-5">
    <div class="container"><p class="m-0 small">Copyright &copy; حرفة Tunisie 2026</p></div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/scripts.js"></script>
</body>
</html>
