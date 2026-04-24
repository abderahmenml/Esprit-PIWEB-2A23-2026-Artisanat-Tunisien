<?php
$pageTitle   = 'Modifier un utilisateur';
$currentPage = 'users';
require_once __DIR__ . '/../../partials/admin_header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Dashboard</a> › <a href="index.php?action=users">Utilisateurs</a> › Modifier #<?= $user['id_user'] ?>
</div>

<div class="form-card">
  <form method="POST" action="index.php?action=editUser&id=<?= $user['id_user'] ?>" data-validate-form>
    <div class="form-grid">

      <div class="form-group">
        <label class="form-label" for="nom">Nom</label>
        <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-error' : '' ?>"
               value="<?= htmlspecialchars($user['nom']) ?>"
               data-validate="required|alpha|max:100"
               data-label="Nom">
        <div class="form-error" id="nom-err"><?= $errors['nom'] ?? '' ?></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-error' : '' ?>"
               value="<?= htmlspecialchars($user['prenom']) ?>"
               data-validate="required|alpha|max:100"
               data-label="Prénom">
        <div class="form-error" id="prenom-err"><?= $errors['prenom'] ?? '' ?></div>
      </div>

      <div class="form-group full">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input type="text" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-error' : '' ?>"
               value="<?= htmlspecialchars($user['email']) ?>"
               data-validate="required|email"
               data-label="Email">
        <div class="form-error" id="email-err"><?= $errors['email'] ?? '' ?></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="role">Rôle</label>
        <select id="role" name="role" class="form-control <?= isset($errors['role']) ? 'is-error' : '' ?>"
                data-validate="not_empty_select"
                data-label="Rôle">
          <?php $allRoles = array_merge(ROLES, ['admin']); ?>
          <?php foreach ($allRoles as $r): ?>
            <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>>
              <?= ucfirst($r) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-error" id="role-err"><?= $errors['role'] ?? '' ?></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="etat_compte">État du compte</label>
        <select id="etat_compte" name="etat_compte" class="form-control <?= isset($errors['etat_compte']) ? 'is-error' : '' ?>"
                data-validate="not_empty_select"
                data-label="État du compte">
          <option value="actif"    <?= $user['etat_compte'] === 'actif'    ? 'selected' : '' ?>>✅ Actif</option>
          <option value="inactif"  <?= $user['etat_compte'] === 'inactif'  ? 'selected' : '' ?>>❌ Inactif</option>
          <option value="suspendu" <?= $user['etat_compte'] === 'suspendu' ? 'selected' : '' ?>>⛔ Suspendu</option>
        </select>
        <div class="form-error" id="etat_compte-err"><?= $errors['etat_compte'] ?? '' ?></div>
      </div>

    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">💾 Enregistrer les modifications</button>
      <a href="index.php?action=users" class="btn btn-secondary">Annuler</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../partials/admin_footer.php'; ?>
