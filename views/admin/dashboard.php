<?php
require_once __DIR__ . '/../partials/admin_header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-number"><?= $stats['total'] ?></div>
    <div class="stat-label">Utilisateurs totaux</div>
  </div>
  <div class="stat-card green">
    <div class="stat-number"><?= $stats['actif'] ?></div>
    <div class="stat-label">Comptes actifs</div>
  </div>
  <div class="stat-card red">
    <div class="stat-number"><?= $stats['inactif'] ?></div>
    <div class="stat-label">Comptes inactifs</div>
  </div>
  <?php foreach ($stats['byRole'] as $r): ?>
  <div class="stat-card brown">
    <div class="stat-number"><?= $r['total'] ?></div>
    <div class="stat-label"><?= ucfirst($r['role']) ?>s</div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Graphique rôles -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">

  <div class="table-card" style="padding:1.5rem;">
    <h3 style="font-family:'Playfair Display',serif;color:var(--brun);margin-bottom:1.2rem;font-size:1rem;">
      📊 Répartition par rôle
    </h3>
    <?php
    $total = max($stats['total'], 1);
    $roleColors = [
      'entrepreneur' => '#8B5A3A',
      'artisan'      => '#7B5EA7',
      'mentor'       => '#2E6B3E',
      'investisseur' => '#C49A6C',
      'admin'        => '#3B2314',
    ];
    foreach ($stats['byRole'] as $r):
      $pct   = round($r['total'] / $total * 100);
      $color = $roleColors[$r['role']] ?? '#aaa';
    ?>
    <div style="margin-bottom:.9rem;">
      <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:.3rem;">
        <span style="font-weight:600;color:var(--brun);"><?= ucfirst($r['role']) ?></span>
        <span style="color:var(--text-muted);"><?= $r['total'] ?> (<?= $pct ?>%)</span>
      </div>
      <div style="height:8px;background:rgba(139,90,58,.12);border-radius:4px;overflow:hidden;">
        <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:4px;transition:width .6s ease;"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Raccourcis -->
  <div class="table-card" style="padding:1.5rem;">
    <h3 style="font-family:'Playfair Display',serif;color:var(--brun);margin-bottom:1.2rem;font-size:1rem;">
      ⚡ Accès rapides
    </h3>
    <div style="display:flex;flex-direction:column;gap:.7rem;">
      <a href="index.php?action=users"       class="btn btn-primary" style="justify-content:center;">👥 Gérer les utilisateurs</a>
      <a href="index.php?action=createUser"  class="btn btn-success" style="justify-content:center;">➕ Ajouter un utilisateur</a>
      <a href="../index.php?page=dashboard"  class="btn btn-secondary" style="justify-content:center;">🌐 Voir le FrontOffice</a>
    </div>
  </div>
</div>

<!-- Derniers inscrits -->
<div class="table-card">
  <div class="table-toolbar">
    <span class="table-toolbar-title">🕐 Derniers utilisateurs inscrits</span>
    <a href="index.php?action=users" class="btn btn-secondary" style="font-size:.8rem;padding:.4rem .9rem;">Voir tout →</a>
  </div>
  <table>
    <thead>
      <tr>
        <th>Nom complet</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Statut</th>
        <th>Date d'inscription</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (array_slice($stats['recent'], 0, 8) as $u): ?>
      <tr>
        <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
        <td><span class="badge badge-<?= $u['etat_compte'] ?>"><?= ucfirst($u['etat_compte']) ?></span></td>
        <td><?= date('d/m/Y', strtotime($u['date_creation'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../partials/admin_footer.php'; ?>
