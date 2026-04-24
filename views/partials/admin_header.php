<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'BackOffice') ?> — CraftLink Admin</title>
  <link rel="stylesheet" href="../public/css/admin.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="../public/assets/logo.png" alt="Logo" class="sidebar-logo">
    <div>
      <div class="sidebar-title">CraftLink</div>
      <div class="sidebar-sub">Administration</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">TABLEAU DE BORD</div>
    <a href="index.php" class="nav-item <?= ($currentPage??'') === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <div class="nav-section-label">GESTION</div>
    <a href="index.php?action=users" class="nav-item <?= ($currentPage??'') === 'users' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Utilisateurs
    </a>

    <div class="nav-section-label">COMPTE</div>
    <a href="<?= APP_URL ?>/index.php" class="nav-item">
      <span class="nav-icon">🌐</span> FrontOffice
    </a>
    <a href="<?= APP_URL ?>/index.php?page=logout" class="nav-item nav-logout">
      <span class="nav-icon">🚪</span> Déconnexion
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user-info">
      <div class="sidebar-avatar">
        <?= strtoupper(substr($_SESSION['prenom'] ?? 'A', 0, 1)) . strtoupper(substr($_SESSION['nom'] ?? 'D', 0, 1)) ?>
      </div>
      <div>
        <div class="sidebar-uname"><?= htmlspecialchars(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?></div>
        <div class="sidebar-urole">Administrateur</div>
      </div>
    </div>
  </div>
</aside>

<main class="main-content">
  <div class="topbar">
    <h1 class="page-heading"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
    <div class="topbar-right">
      <?php if (!empty($_SESSION['flash'])): ?>
        <div class="flash flash-<?= $_SESSION['flash']['type'] ?>">
          <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="content-area">
