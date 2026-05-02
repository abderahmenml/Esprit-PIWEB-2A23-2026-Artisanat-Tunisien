<?php
include '../../Controller/IdeaController.php';
$ideaC = new IdeaController();
$projects = $ideaC->listProjects();
$currentUserId = getCurrentUserId();

$editId = 0;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
}
$editProject = null;
if ($editId > 0) {
  $editProject = $ideaC->showProjectAny($editId);
}

$editSkills = [];
$editMaterials = [];
if ($editProject) {
  $editSkills = $ideaC->getSkillsForProject($editId);
  $editMaterials = $ideaC->getMaterialsForProject($editId);
}

$selectedOuvert = '';
$selectedEnCours = '';
$selectedFerme = '';
if ($editProject) {
  $statusValue = '';
  if (isset($editProject['status'])) {
    $statusValue = $editProject['status'];
  }

  if ($statusValue === 'Ouvert') {
    $selectedOuvert = 'selected';
  }
  if ($statusValue === 'En cours') {
    $selectedEnCours = 'selected';
  }
  if ($statusValue === 'Ferme') {
    $selectedFerme = 'selected';
  }
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$total = count($projects);
$open = 0;
$progress = 0;
$closed = 0;
foreach ($projects as $project) {
  $statusValue = '';
  if (isset($project['status'])) {
    $statusValue = (string)$project['status'];
  }
  $status = strtolower($statusValue);
    if ($status === 'ouvert') {
        $open += 1;
    } elseif ($status === 'en cours') {
        $progress += 1;
    } elseif ($status === 'ferme') {
        $closed += 1;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Backoffice - Idees de projet</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/backoffice.css?v=2">
</head>
<body>
  <header class="bo-topbar">
    <div>
      <h1>Backoffice - Idees</h1>
    </div>
    <div>
      <?php if ($currentUserId > 0): ?>
        <a class="btn" href="../FrontOffice/logout.php">Deconnexion</a>
      <?php else: ?>
        <a class="btn" href="../FrontOffice/login.php">Connexion</a>
      <?php endif; ?>
      <a class="btn" href="../FrontOffice/index.php">Retour site</a>
    </div>
  </header>

  <main class="bo-layout">
    <aside class="bo-sidebar">
      <div class="bo-sidebar-header">
        <span>Admin</span>
        <strong>Back Office</strong>
      </div>
      <nav class="bo-nav">
        <div class="bo-nav-section">
          <div class="bo-nav-title">Tableau de bord</div>
          <a class="bo-nav-link is-active" href="index.php">Dashboard</a>
        </div>

        <div class="bo-nav-section">
          <div class="bo-nav-title">Modules</div>
          <a class="bo-nav-link" href="#">
            Profils Pro
            <span class="bo-nav-badge">3</span>
          </a>
          <a class="bo-nav-link" href="#">Utilisateurs</a>
          <a class="bo-nav-link" href="#">Projets</a>
          <a class="bo-nav-link" href="#">Formations</a>
          <a class="bo-nav-link" href="#">Investissements</a>
          <a class="bo-nav-link" href="#">Offres d'emploi</a>
        </div>

        <div class="bo-nav-section">
          <div class="bo-nav-title">Systeme</div>
          <a class="bo-nav-link" href="#">Competences</a>
          <a class="bo-nav-link" href="#">Parametres</a>
          <a class="bo-nav-link" href="#">Logs</a>
        </div>
      </nav>
      <div class="bo-sidebar-footer">
        <div class="bo-sidebar-avatar">SA</div>
        <div class="bo-sidebar-user">
          <strong>Super Admin</strong>
          <span>CraftLink Tunisie</span>
        </div>
      </div>
    </aside>

    <div class="bo-content">
      <section class="stats">
      <div class="stat">
        <span>Total</span>
        <strong><?php echo e($total); ?></strong>
      </div>
      <div class="stat">
        <span>Ouvert</span>
        <strong><?php echo e($open); ?></strong>
      </div>
      <div class="stat">
        <span>En cours</span>
        <strong><?php echo e($progress); ?></strong>
      </div>
      <div class="stat">
        <span>Ferme</span>
        <strong><?php echo e($closed); ?></strong>
      </div>
    </section>

    <?php if ($editProject): ?>
      <section class="card">
        <h2>Modifier une idee</h2>
        <form method="post" action="updateIdea.php" class="edit-form">
          <input type="hidden" name="id" value="<?php echo e($editProject['id_projet']); ?>">

          <label>
            Titre
            <input type="text" name="title" value="<?php echo e($editProject['titre']); ?>" required>
          </label>

          <label>
            Categorie
            <input type="text" name="category" value="<?php echo e($editProject['categorie']); ?>">
          </label>

          <label>
            Statut
            <select name="status">
              <option value="">Choisir</option>
              <option value="Ouvert" <?php echo $selectedOuvert; ?>>Ouvert</option>
              <option value="En cours" <?php echo $selectedEnCours; ?>>En cours</option>
              <option value="Ferme" <?php echo $selectedFerme; ?>>Ferme</option>
            </select>
          </label>

          <label>
            Budget
            <input type="number" name="budget" step="0.01" value="<?php echo e($editProject['budget_min']); ?>">
          </label>

          <label>
            Description
            <textarea name="description" rows="4"><?php echo e($editProject['description']); ?></textarea>
          </label>

          <label>
            Competences
            <div class="list-builder" id="skillsBuilder">
              <div class="builder-head">
                <p>Ajoutez le nom et le niveau de chaque competence.</p>
                <button type="button" class="btn small" id="addSkillBtn">+ Ajouter competence</button>
              </div>
              <div id="skillsRows" class="builder-rows">
                <?php if (count($editSkills) > 0): ?>
                  <?php foreach ($editSkills as $skill): ?>
                    <?php
                      $skillNameValue = '';
                      $skillLevelValue = '';
                      if (isset($skill['nom'])) {
                        $skillNameValue = $skill['nom'];
                      }
                      if (isset($skill['skill_level'])) {
                        $skillLevelValue = $skill['skill_level'];
                      }
                    ?>
                    <div class="builder-row skill-row">
                      <input class="row-name" name="skills_name[]" type="text" placeholder="Nom de la competence" value="<?php echo e($skillNameValue); ?>">
                      <select class="row-level" name="skills_level[]">
                        <option value="">Niveau</option>
                        <option <?php if ($skillLevelValue === 'Debutant') { echo 'selected'; } ?>>Debutant</option>
                        <option <?php if ($skillLevelValue === 'Intermediaire') { echo 'selected'; } ?>>Intermediaire</option>
                        <option <?php if ($skillLevelValue === 'Avance') { echo 'selected'; } ?>>Avance</option>
                        <option <?php if ($skillLevelValue === 'Expert') { echo 'selected'; } ?>>Expert</option>
                      </select>
                      <button type="button" class="btn small danger row-remove">Retirer</button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="builder-row skill-row">
                    <input class="row-name" name="skills_name[]" type="text" placeholder="Nom de la competence" value="">
                    <select class="row-level" name="skills_level[]">
                      <option value="">Niveau</option>
                      <option>Debutant</option>
                      <option>Intermediaire</option>
                      <option>Avance</option>
                      <option>Expert</option>
                    </select>
                    <button type="button" class="btn small danger row-remove">Retirer</button>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </label>

          <label>
            Materiaux
            <div class="list-builder" id="materialsBuilder">
              <div class="builder-head">
                <p>Ajoutez le nom, la quantite et le prix unitaire de chaque materiau.</p>
                <button type="button" class="btn small" id="addMaterialBtn">+ Ajouter materiau</button>
              </div>
              <div id="materialsRows" class="builder-rows">
                <?php if (count($editMaterials) > 0): ?>
                  <?php foreach ($editMaterials as $material): ?>
                    <?php
                      $materialNameValue = '';
                      $materialQtyValue = '';
                      $materialPriceValue = '';
                      if (isset($material['nom_materiel'])) {
                        $materialNameValue = $material['nom_materiel'];
                      }
                      if (isset($material['quantite'])) {
                        $materialQtyValue = $material['quantite'];
                      }
                      if (isset($material['prix_unitaire'])) {
                        $materialPriceValue = $material['prix_unitaire'];
                      }
                    ?>
                    <div class="builder-row material-row">
                      <input class="row-name" name="materials_name[]" type="text" placeholder="Nom du materiau" value="<?php echo e($materialNameValue); ?>">
                      <input class="row-qty" name="materials_qty[]" type="number" min="1" step="1" placeholder="Quantite" value="<?php echo e($materialQtyValue); ?>">
                      <input class="row-price" name="materials_price[]" type="number" min="0" step="0.01" placeholder="Prix unitaire" value="<?php echo e($materialPriceValue); ?>">
                      <button type="button" class="btn small danger row-remove">Retirer</button>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="builder-row material-row">
                    <input class="row-name" name="materials_name[]" type="text" placeholder="Nom du materiau" value="">
                    <input class="row-qty" name="materials_qty[]" type="number" min="1" step="1" placeholder="Quantite" value="">
                    <input class="row-price" name="materials_price[]" type="number" min="0" step="0.01" placeholder="Prix unitaire" value="">
                    <button type="button" class="btn small danger row-remove">Retirer</button>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </label>

          <div class="form-actions">
            <a class="btn" href="index.php">Annuler</a>
            <button class="btn primary" type="submit">Enregistrer</button>
          </div>
        </form>
      </section>
    <?php endif; ?>

    <section class="card">
      <h2>Liste des idees</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Titre</th>
              <th>Utilisateur</th>
              <th>Categorie</th>
              <th>Statut</th>
              <th>Budget</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($projects) === 0): ?>
              <tr>
                <td colspan="8">Aucune idee pour le moment.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($projects as $project): ?>
                <?php
                  $categoryDisplay = '-';
                      if (isset($project['categorie']) && $project['categorie'] !== '') {
                        $categoryDisplay = $project['categorie'];
                      }

                  $statusDisplay = '-';
                      if (isset($project['status']) && $project['status'] !== '') {
                        $statusDisplay = $project['status'];
                      }

                  $dateDisplay = '-';
                      if (isset($project['date_creation']) && $project['date_creation'] !== '') {
                        $dateDisplay = $project['date_creation'];
                      }

                  $ownerDisplay = '-';
                  if (isset($project['nom']) && $project['nom'] !== '') {
                    $ownerDisplay = $project['nom'];
                  }
                  if (isset($project['prenom']) && $project['prenom'] !== '') {
                    if ($ownerDisplay === '-') {
                      $ownerDisplay = $project['prenom'];
                    } else {
                      $ownerDisplay = $ownerDisplay . ' ' . $project['prenom'];
                    }
                  }
                ?>
                <tr>
                  <td><?php echo e($project['id_projet']); ?></td>
                  <td><?php echo e($project['titre']); ?></td>
                  <td><?php echo e($ownerDisplay); ?></td>
                  <td><?php echo e($categoryDisplay); ?></td>
                  <td><?php echo e($statusDisplay); ?></td>
                  <td>
                    <?php
                      if ($project['budget_min'] !== null && $project['budget_min'] !== '') {
                          echo e(number_format((float)$project['budget_min'], 0, ',', ' ')) . ' DT';
                      } else {
                          echo '-';
                      }
                    ?>
                  </td>
                  <td><?php echo e($dateDisplay); ?></td>
                  <td class="actions">
                    <a class="btn small" href="index.php?edit=<?php echo e($project['id_projet']); ?>" onclick="return confirm('Voulez-vous modifier cette idee ?');">Editer</a>
                    <form method="post" action="deleteIdea.php" class="inline-form" onsubmit="return confirm('Voulez-vous supprimer cette idee ?');">
                      <input type="hidden" name="id" value="<?php echo e($project['id_projet']); ?>">
                      <button class="btn small danger" type="submit">Supprimer</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
    </div>
  </main>

  <script>
    (function () {
      const skillsRows = document.getElementById('skillsRows');
      const addSkillBtn = document.getElementById('addSkillBtn');
      const materialsRows = document.getElementById('materialsRows');
      const addMaterialBtn = document.getElementById('addMaterialBtn');

      function addSkillRow() {
        if (!skillsRows) {
          return;
        }
        const row = document.createElement('div');
        row.className = 'builder-row skill-row';
        row.innerHTML =
          '<input class="row-name" name="skills_name[]" type="text" placeholder="Nom de la competence" value="">' +
          '<select class="row-level" name="skills_level[]">' +
          '<option value="">Niveau</option>' +
          '<option>Debutant</option>' +
          '<option>Intermediaire</option>' +
          '<option>Avance</option>' +
          '<option>Expert</option>' +
          '</select>' +
          '<button type="button" class="btn small danger row-remove">Retirer</button>';
        skillsRows.appendChild(row);
      }

      function addMaterialRow() {
        if (!materialsRows) {
          return;
        }
        const row = document.createElement('div');
        row.className = 'builder-row material-row';
        row.innerHTML =
          '<input class="row-name" name="materials_name[]" type="text" placeholder="Nom du materiau" value="">' +
          '<input class="row-qty" name="materials_qty[]" type="number" min="1" step="1" placeholder="Quantite" value="">' +
          '<input class="row-price" name="materials_price[]" type="number" min="0" step="0.01" placeholder="Prix unitaire" value="">' +
          '<button type="button" class="btn small danger row-remove">Retirer</button>';
        materialsRows.appendChild(row);
      }

      if (addSkillBtn) {
        addSkillBtn.addEventListener('click', addSkillRow);
      }
      if (addMaterialBtn) {
        addMaterialBtn.addEventListener('click', addMaterialRow);
      }

      document.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
          return;
        }
        if (!target.classList.contains('row-remove')) {
          return;
        }
        const row = target.closest('.builder-row');
        if (row) {
          row.remove();
        }
      });
    })();
  </script>
</body>
</html>
