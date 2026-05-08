<?php
$pageTitle   = 'Ajouter un utilisateur';
$currentPage = 'users';
require_once __DIR__ . '/../../partials/admin_header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Dashboard</a> › <a href="index.php?action=users">Utilisateurs</a> › Ajouter
</div>

<div class="form-card">
  <form method="POST" action="index.php?action=createUser" data-validate-form>
    <div class="form-grid">

      <!-- Nom -->
      <div class="form-group">
        <label class="form-label" for="nom">Nom</label>
        <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-error' : '' ?>"
               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
               data-validate="required|alpha|max:100"
               data-label="Nom"
               autocomplete="off">
        <div class="form-error" id="nom-err"><?= $errors['nom'] ?? '' ?></div>
      </div>

      <!-- Prénom -->
      <div class="form-group">
        <label class="form-label" for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-error' : '' ?>"
               value="<?= htmlspecialchars($old['prenom'] ?? '') ?>"
               data-validate="required|alpha|max:100"
               data-label="Prénom"
               autocomplete="off">
        <div class="form-error" id="prenom-err"><?= $errors['prenom'] ?? '' ?></div>
      </div>

      <!-- Email -->
      <div class="form-group full">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input type="text" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-error' : '' ?>"
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               data-validate="required|email"
               data-label="Email"
               autocomplete="off">
        <div class="form-error" id="email-err"><?= $errors['email'] ?? '' ?></div>
      </div>

      <!-- Mot de passe -->
      <div class="form-group">
        <label class="form-label" for="password">Mot de passe</label>
        <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-error' : '' ?>"
               data-validate="required|min:6"
               data-label="Mot de passe">
        <div class="form-error" id="password-err"><?= $errors['password'] ?? '' ?></div>
      </div>

      <!-- Confirmation -->
      <div class="form-group">
        <label class="form-label" for="confirm">Confirmer le mot de passe</label>
        <input type="password" id="confirm" name="confirm" class="form-control <?= isset($errors['confirm']) ? 'is-error' : '' ?>"
               data-validate="required|matches:password"
               data-label="Confirmation">
        <div class="form-error" id="confirm-err"><?= $errors['confirm'] ?? '' ?></div>
      </div>

      <!-- Rôle -->
      <div class="form-group">
        <label class="form-label" for="role">Rôle</label>
        <select id="role" name="role" class="form-control <?= isset($errors['role']) ? 'is-error' : '' ?>"
                data-validate="not_empty_select"
                data-label="Rôle">
          <option value="">— Sélectionner un rôle —</option>
          <?php foreach (ROLES as $r): ?>
            <option value="<?= $r ?>" <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>>
              <?= ucfirst($r) ?>
            </option>
          <?php endforeach; ?>
          <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <div class="form-error" id="role-err"><?= $errors['role'] ?? '' ?></div>
      </div>

      <!-- État du compte -->
      <div class="form-group">
        <label class="form-label" for="etat_compte">État du compte</label>
        <select id="etat_compte" name="etat_compte" class="form-control"
                data-validate="not_empty_select"
                data-label="État du compte">
          <option value="actif"   <?= ($old['etat_compte'] ?? 'actif') === 'actif'   ? 'selected' : '' ?>>✅ Actif</option>
          <option value="inactif" <?= ($old['etat_compte'] ?? '')       === 'inactif' ? 'selected' : '' ?>>❌ Inactif</option>
          <option value="suspendu"<?= ($old['etat_compte'] ?? '')       === 'suspendu'? 'selected' : '' ?>>⛔ Suspendu</option>
        </select>
        <div class="form-error" id="etat_compte-err"></div>
      </div>

    </div><!-- /.form-grid -->

    <div class="form-actions">
      <button type="submit" class="btn btn-success">✅ Créer l'utilisateur</button>
      <a href="index.php?action=users" class="btn btn-secondary">Annuler</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../partials/admin_footer.php'; ?>
