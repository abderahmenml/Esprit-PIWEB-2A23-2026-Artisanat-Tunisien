<?php
$pageTitle = 'Gestion des utilisateurs';
$currentPage = 'users';
require_once __DIR__ . '/../../partials/admin_header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Dashboard</a> > Utilisateurs
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-number"><?= $stats['total'] ?></div>
    <div class="stat-label">Total utilisateurs</div>
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

<div class="table-card">
  <div class="table-toolbar">
    <span class="table-toolbar-title">Liste des utilisateurs</span>

    <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" id="live-search" placeholder="Rechercher..." autocomplete="off">
      </div>

      <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
        <input type="hidden" name="action" value="users">
        <select name="role" class="filter-select" onchange="this.form.submit()">
          <option value="">Tous les roles</option>
          <?php foreach (ROLES as $r): ?>
            <option value="<?= $r ?>" <?= ($filters['role'] ?? '') === $r ? 'selected' : '' ?>>
              <?= ucfirst($r) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="">Tous les statuts</option>
          <option value="actif" <?= ($filters['etat_compte'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
          <option value="inactif" <?= ($filters['etat_compte'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
          <option value="suspendu" <?= ($filters['etat_compte'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
        </select>
      </form>

      <a href="index.php?action=createUser" class="btn btn-primary">+ Ajouter</a>
    </div>
  </div>

  <?php if (empty($users)): ?>
    <div class="empty-state">
      <div class="empty-icon">👤</div>
      <p>Aucun utilisateur trouve.</p>
    </div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nom complet</th>
          <th>Email</th>
          <th>Role</th>
          <th>Statut</th>
          <th>Inscription</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <?php
          $isAdminAccount = ($u['email'] ?? '') === 'admin@craftlink.tn' || ($u['role'] ?? '') === 'admin';
          $isSelf = (int) $u['id_user'] === (int) ($_SESSION['user_id'] ?? 0);
          $isSecurityLocked = !empty($u['permanently_locked'])
              || (!empty($u['blocked_until']) && strtotime((string) $u['blocked_until']) > time());
          $isLocked = !empty($u['is_blocked']) || $isSecurityLocked;
          ?>
          <tr>
            <td><?= $u['id_user'] ?></td>
            <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-<?= htmlspecialchars($u['role']) ?>"><?= ucfirst($u['role']) ?></span></td>
            <td>
              <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                <select class="filter-select status-toggle"
                        data-id="<?= $u['id_user'] ?>"
                        style="font-size:.75rem;padding:.3rem .6rem;">
                  <option value="actif" <?= $u['etat_compte'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                  <option value="inactif" <?= $u['etat_compte'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                  <option value="suspendu" <?= $u['etat_compte'] === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                </select>
                <span class="badge badge-<?= $isLocked ? 'danger' : 'success' ?>">
                  <?= $isLocked ? 'Bloque' : 'Debloque' ?>
                </span>
              </div>
            </td>
            <td><?= date('d/m/Y', strtotime($u['date_creation'])) ?></td>
            <td>
              <div class="actions">
                <a href="index.php?action=showUser&id=<?= $u['id_user'] ?>" class="btn-icon btn-view" title="Voir">👁</a>
                <a href="index.php?action=editUser&id=<?= $u['id_user'] ?>" class="btn-icon btn-edit" title="Modifier">✏️</a>

                <?php if (!$isAdminAccount && !$isSelf): ?>
                  <form method="POST" action="index.php?action=toggleBlock" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $u['id_user'] ?>">
                    <button type="submit"
                            class="btn-icon <?= $isLocked ? 'btn-view' : 'btn-delete' ?>"
                            title="<?= $isLocked ? 'Debloquer' : 'Bloquer' ?>"
                            style="width:auto;min-width:92px;padding:0 .7rem;border:none;cursor:pointer;">
                      <?= $isLocked ? 'Debloquer' : 'Bloquer' ?>
                    </button>
                  </form>
                <?php endif; ?>

                <a href="#"
                   class="btn-icon btn-delete btn-delete-trigger"
                   data-href="index.php?action=deleteUser&id=<?= $u['id_user'] ?>"
                   data-name="<?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>"
                   title="Supprimer">🗑</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="confirm-modal">
  <div class="modal-box">
    <div class="modal-title">Confirmer la suppression</div>
    <div class="modal-body"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary modal-cancel">Annuler</button>
      <button class="btn btn-danger modal-confirm">Supprimer definitivement</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/admin_footer.php'; ?>
