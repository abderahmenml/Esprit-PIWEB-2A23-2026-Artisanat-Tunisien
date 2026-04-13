<?php
session_start();
require_once 'config.php';

$pdo = getPDO(); 

$erreur = '';

// Connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['register'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email AND etat_compte = 'actif'");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_role'] = $user['role'];

        header('Location: profil_professionnel.php'); // Redirige vers le profil professionnel
        exit();
    } else {
        $erreur = "Email ou mot de passe incorrect";
    }
}

// Gestion inscription
if (isset($_POST['register'])) {
    $nom = trim($_POST['reg_nom']);
    $prenom = trim($_POST['reg_prenom']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $erreur_insc = '';
    // Vérifier unicité email
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        $erreur_insc = "Cet email est déjà utilisé.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (nom, prenom, email, mot_de_passe, etat_compte) VALUES (:nom, :prenom, :email, :mdp, 'actif')");
        $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':mdp' => $hash
        ]);
        // Connexion automatique
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_role'] = 'utilisateur';
        header('Location: profil_professionnel.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CraftLink Tunisie — Connexion</title>
<link href="style.css" rel="stylesheet">
</head>
<body>
<!-- ── LEFT ── -->
<div class="left-panel">
  <div class="brand">
    <div class="logo-ring">
      <img src="logo.png" alt="CraftLink Logo" class="logo-img">
    </div>
    <div class="brand-title">CraftLink Tunisie</div>
    <div class="brand-arabic">حرفة تونس</div>
    <div class="brand-sub">L'artisanat tunisien, à l'ère du numérique.</div>
  </div>
  <div class="divider-ornament"><div class="divider-diamond"></div></div>
  <div class="roles-title">Rejoignez notre écosystème</div>
  <div class="roles">
    <div class="role-card entrepreneur">🎨 Entrepreneur</div>
    <div class="role-card mentor">🧑‍🎨 Mentor / Artisan</div>
    <div class="role-card investisseur">💼 Investisseur</div>
  </div>
  <p class="left-quote">
    « Connecter les créateurs, les artisans et les investisseurs pour faire vivre l'artisanat tunisien. »
  </p>
</div>
<!-- ── RIGHT ── -->
<div class="right-panel">
  <div class="form-header">
    <div class="form-label-small">Bienvenue</div>
    <div class="form-title">Connexion à votre espace</div>
    <div class="form-subtitle">Accédez à votre tableau de bord CraftLink</div>
  </div>
  <!-- Role tabs -->
  <div class="role-tabs">
    <button class="role-tab active" type="button" onclick="setRole(this,'entrepreneur')">
      <span class="tab-icon">🎨</span> Entrepreneur
    </button>
    <button class="role-tab" type="button" onclick="setRole(this,'artisan')">
      <span class="tab-icon">🧑‍🎨</span> Artisan
    </button>
    <button class="role-tab" type="button" onclick="setRole(this,'mentor')">
      <span class="tab-icon">🎓</span> Mentor
    </button>
    <button class="role-tab" type="button" onclick="setRole(this,'investisseur')">
      <span class="tab-icon">💼</span> Investisseur
    </button>
  </div>
  <form method="POST" action="" autocomplete="on" class="login-form">
    <!-- Email -->
    <div class="field-group">
      <label class="field-label" for="email">Adresse e-mail</label>
      <div class="field-wrap">
        <span class="field-icon">✉</span>
        <input type="email" id="email" name="email" placeholder="votre@email.com" autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
      </div>
      <div class="error-msg" id="email-err" style="display:none;">Veuillez entrer une adresse e-mail valide.</div>
    </div>
    <!-- Password -->
    <div class="field-group">
      <label class="field-label" for="password">Mot de passe</label>
      <div class="field-wrap">
        <span class="field-icon">🔒</span>
        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        <button class="toggle-pw" type="button" onclick="togglePw()" id="pw-toggle" title="Afficher / masquer">👁</button>
      </div>
      <div class="error-msg" id="pw-err" style="display:none;">Le mot de passe est requis.</div>
    </div>
    <!-- PHP Error -->
    <?php if (!empty($erreur)): ?>
      <div class="error-msg" style="display:block; color:#b00; margin-bottom:10px; text-align:center;">
        <?php echo $erreur; ?>
      </div>
    <?php endif; ?>
    <!-- Remember / Forgot -->
    <div class="options-row">
      <label class="checkbox-label">
        <input type="checkbox" name="remember"> Se souvenir de moi
      </label>
      <a href="#" class="forgot-link">Mot de passe oublié ?</a>
    </div>
    <!-- Submit -->
    <button class="btn-login" type="submit">Se connecter →</button>
  </form>
  <div class="or-divider"><span class="or-text">Nouveau sur CraftLink ?</span></div>
  <div class="register-link">
    Pas encore de compte ? <a href="#" id="open-register-modal" style="color:#217a3a;font-weight:600">Créez votre profil gratuitement</a>
  </div>
  <div class="form-footer">
    <span class="footer-brand">Projet CraftLink · 2025–2026</span>
    <div class="footer-lang">
      <button class="lang-btn active" type="button">FR</button>
      <button class="lang-btn" type="button">AR</button>
      <button class="lang-btn" type="button">EN</button>
    </div>
  </div>
</div>
<!-- Modal inscription -->
<div class="modal-overlay" id="register-modal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h2>Créer un compte</h2>
      <button type="button" class="modal-close" onclick="closeRegisterModal()">✕</button>
    </div>
    <form method="POST" action="" class="register-form">
      <div class="form-group">
        <label>Nom</label>
        <input type="text" name="reg_nom" required>
      </div>
      <div class="form-group">
        <label>Prénom</label>
        <input type="text" name="reg_prenom" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="reg_email" required>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="reg_password" required>
      </div>
      <?php if (!empty($erreur_insc ?? '')): ?>
        <div class="error-msg" style="color:#b00; margin-bottom:10px; text-align:center;">
          <?php echo $erreur_insc; ?>
        </div>
      <?php endif; ?>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" onclick="closeRegisterModal()">Annuler</button>
        <button type="submit" class="btn-primary" name="register">Créer mon compte</button>
      </div>
    </form>
  </div>
</div>
<script>
  function setRole(el, role) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
  }
  function togglePw() {
    const pw = document.getElementById('password');
    const btn = document.getElementById('pw-toggle');
    if (pw.type === 'password') { pw.type = 'text'; btn.textContent = '🙈'; }
    else { pw.type = 'password'; btn.textContent = '👁'; }
  }
  // Language toggle
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
  // Modal inscription
  const regModal = document.getElementById('register-modal');
  document.getElementById('open-register-modal').onclick = function(e) {
    e.preventDefault();
    regModal.style.display = 'block';
  }
  function closeRegisterModal() {
    regModal.style.display = 'none';
  }
</script>
</body>
</html>