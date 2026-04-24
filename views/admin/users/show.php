<?php
$pageTitle   = 'Détail utilisateur';
$currentPage = 'users';
require_once __DIR__ . '/../../partials/admin_header.php';

$roleIcons = [
    'entrepreneur' => '🎨',
    'artisan'      => '🧑‍🎨',
    'mentor'       => '🎓',
    'investisseur' => '💼',
    'admin'        => '🛡️',
];
?>

<div class="breadcrumb">
  <a href="index.php">Dashboard</a> ›
  <a href="index.php?action=users">Utilisateurs</a> ›
  <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
</div>

<!-- Carte profil -->
<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;">

  <div class="table-card" style="padding:2rem;text-align:center;">
    <div style="width:80px;height:80px;border-radius:50%;background:var(--brun);color:var(--creme);
                display:flex;align-items:center;justify-content:center;
                font-family:'Playfair Display',serif;font-size:1.8rem;margin:0 auto 1rem;">
      <?= strtoupper(substr($user['prenom'],0,1) . substr($user['nom'],0,1)) ?>
    </div>
    <div style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--brun);font-weight:700;">
      <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
    </div>
    <div style="margin-top:.4rem;">
      <span class="badge badge-<?= $user['role'] ?>" style="font-size:.85rem;padding:.4rem .9rem;">
        <?= ($roleIcons[$user['role']] ?? '') . ' ' . ucfirst($user['role']) ?>
      </span>
    </div>
    <div style="margin-top:.8rem;">
      <span class="badge badge-<?= $user['etat_compte'] ?>">
        <?= ucfirst($user['etat_compte']) ?>
      </span>
    </div>
    <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.6rem;">
      <a href="index.php?action=editUser&id=<?= $user['id_user'] ?>" class="btn btn-primary" style="justify-content:center;">✏️ Modifier</a>
      <button onclick="toggleBlock(<?= $user['id_user'] ?>, '<?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>')"
              class="btn <?= $user['is_blocked'] ? 'btn-success' : 'btn-warning' ?>" style="justify-content:center;">
        <?= $user['is_blocked'] ? '🔓 Débloquer' : '🚫 Bloquer' ?>
      </button>
      <a href="#"
         class="btn btn-danger btn-delete-trigger"
         data-href="index.php?action=deleteUser&id=<?= $user['id_user'] ?>"
         data-name="<?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>"
         style="justify-content:center;">🗑 Supprimer</a>
    </div>
  </div>

  <div class="table-card" style="padding:2rem;">
    <h3 style="font-family:'Playfair Display',serif;color:var(--brun);margin-bottom:1.2rem;">
      Informations du compte
    </h3>
    <div class="detail-grid">
      <div class="detail-item">
        <div class="detail-label">ID</div>
        <div class="detail-value">#<?= $user['id_user'] ?></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Date d'inscription</div>
        <div class="detail-value"><?= date('d/m/Y', strtotime($user['date_creation'])) ?></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Nom</div>
        <div class="detail-value"><?= htmlspecialchars($user['nom']) ?></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Prénom</div>
        <div class="detail-value"><?= htmlspecialchars($user['prenom']) ?></div>
      </div>
      <div class="detail-item" style="grid-column:1/-1;">
        <div class="detail-label">Adresse e-mail</div>
        <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Rôle</div>
        <div class="detail-value">
          <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
        </div>
      </div>
      <div class="detail-item">
        <div class="detail-label">État du compte</div>
        <div class="detail-value">
          <span class="badge badge-<?= $user['etat_compte'] ?>"><?= ucfirst($user['etat_compte']) ?></span>
        </div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Blocage</div>
        <div class="detail-value">
          <span class="badge badge-<?= $user['is_blocked'] ? 'danger' : 'success' ?>">
            <?= $user['is_blocked'] ? '🚫 Bloqué' : '✅ Débloqué' ?>
          </span>
        </div>
      </div>
    </div>

    <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(139,90,58,.1);">
      <h4 style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem;text-transform:uppercase;letter-spacing:1px;">
        Modifier le statut rapidement
      </h4>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <?php foreach (['actif','inactif','suspendu'] as $s): ?>
          <?php if ($s !== $user['etat_compte']): ?>
          <button onclick="setStatus(<?= $user['id_user'] ?>, '<?= $s ?>')"
                  class="btn btn-secondary" style="font-size:.8rem;padding:.4rem .9rem;">
            <?= $s === 'actif' ? '✅' : ($s === 'inactif' ? '❌' : '⛔') ?> Marquer <?= $s ?>
          </button>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal suppression -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal-box">
    <div class="modal-title">⚠️ Confirmer la suppression</div>
    <div class="modal-body"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary modal-cancel">Annuler</button>
      <button class="btn btn-danger modal-confirm">Supprimer définitivement</button>
    </div>
  </div>
</div>

<script>
function setStatus(id, status) {
  const fd = new FormData();
  fd.append('id', id);
  fd.append('status', status);
  fetch('index.php?action=toggleStatus', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) { location.reload(); }
      else { alert('Erreur : ' + d.message); }
    });
}

function toggleBlock(id, userName) {
  if (!confirm('Êtes-vous sûr de vouloir basculer le blocage de ' + userName + ' ?')) {
    return;
  }
  
  const fd = new FormData();
  fd.append('id', id);
  fetch('index.php?action=users&method=toggleBlock', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) { 
        location.reload(); 
      }
      else { 
        alert('Erreur : ' + d.message); 
      }
    });
}
</script>

<?php require_once __DIR__ . '/../../partials/admin_footer.php'; ?>
