<?php
$baseUrl = rtrim(APP_URL, '/');
if (preg_match('/\.html?$/i', $baseUrl)) {
  $baseUrl = rtrim(dirname($baseUrl), '/');
}
$frontOfficeUrl = $baseUrl . '/View/FrontOffice/homepage.html';
$logoutUrl = $baseUrl . '/View/FrontOffice/logout.php';
$adminCssUrl = $baseUrl . '/public/css/admin.css';
$logoUrl = $baseUrl . '/View/FrontOffice/assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'BackOffice') ?> — CraftLink Admin</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($adminCssUrl, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <?php if (!empty($extraStyles) && is_array($extraStyles)): ?>
    <?php foreach ($extraStyles as $extraStyle): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars((string) $extraStyle, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="sidebar-logo">
    <div>
      <div class="sidebar-title">CraftLink</div>
      <div class="sidebar-sub">Administration</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">TABLEAU DE BORD</div>
    <a href="<?= htmlspecialchars($baseUrl . '/admin/index.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= ($currentPage??'') === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <div class="nav-section-label">GESTION</div>
    <a href="<?= htmlspecialchars($baseUrl . '/admin/index.php?action=users', ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= ($currentPage??'') === 'users' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Utilisateurs
    </a>
    <a href="<?= htmlspecialchars($baseUrl . '/admin/index.php?action=ideas', ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= ($currentPage??'') === 'ideas' ? 'active' : '' ?>">
      <span class="nav-icon">💡</span> Gestion des idées
    </a>

    <div class="nav-section-label">COMPTE</div>
    <a href="<?= htmlspecialchars($frontOfficeUrl, ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
      <span class="nav-icon">🌐</span> FrontOffice
    </a>
    <a href="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" class="nav-item nav-logout">
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
