<?php
include '../../Controller/IdeaController.php';
$ideaC = new IdeaController();
$currentUserId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($currentUserId <= 0) {
    header('Location: login.php');
    exit;
  }

  $action = '';
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
  }

  if ($action === 'delete') {
    $id = 0;
    if (isset($_POST['id'])) {
      $id = (int)$_POST['id'];
    }
    if ($id > 0) {
      $ideaC->deleteIdea($id);
    }
    header('Location: index.php');
    exit;
  }

  if ($action === 'save' || $action === 'update') {
    $title = '';
    if (isset($_POST['title'])) {
      $title = trim($_POST['title']);
    }
    if ($title === '') {
      header('Location: index.php');
      exit;
    }

    $category = '';
    if (isset($_POST['category'])) {
      $category = trim($_POST['category']);
    }

    $status = '';
    if (isset($_POST['status'])) {
      $status = trim($_POST['status']);
    }

    $budgetRaw = '';
    if (isset($_POST['budget'])) {
      $budgetRaw = trim($_POST['budget']);
    }

    $budget = null;
    if ($budgetRaw !== '') {
      $budget = (float)$budgetRaw;
    }

    $description = '';
    if (isset($_POST['description'])) {
      $description = trim($_POST['description']);
    }

    $skillsNames = [];
    if (isset($_POST['skills_name']) && is_array($_POST['skills_name'])) {
      $skillsNames = $_POST['skills_name'];
    }

    $skillsLevels = [];
    if (isset($_POST['skills_level']) && is_array($_POST['skills_level'])) {
      $skillsLevels = $_POST['skills_level'];
    }

    $materialsNames = [];
    if (isset($_POST['materials_name']) && is_array($_POST['materials_name'])) {
      $materialsNames = $_POST['materials_name'];
    }

    $materialsQty = [];
    if (isset($_POST['materials_qty']) && is_array($_POST['materials_qty'])) {
      $materialsQty = $_POST['materials_qty'];
    }

    $materialsPrice = [];
    if (isset($_POST['materials_price']) && is_array($_POST['materials_price'])) {
      $materialsPrice = $_POST['materials_price'];
    }

    if ($action === 'save') {
      $idea = new Idea(null, $title, $budget, $status, $category, $description, null);
      $ideaC->addIdea($idea, $skillsNames, $skillsLevels, $materialsNames, $materialsQty, $materialsPrice);
      header('Location: index.php');
      exit;
    }

    if ($action === 'update') {
      $id = 0;
      if (isset($_POST['id'])) {
        $id = (int)$_POST['id'];
      }
      if ($id <= 0) {
        header('Location: index.php');
        exit;
      }

      $idea = new Idea($id, $title, $budget, $status, $category, $description, null);
      $ideaC->updateIdeaWithDetails($idea, $id, $skillsNames, $skillsLevels, $materialsNames, $materialsQty, $materialsPrice);
      header('Location: index.php');
      exit;
    }
  }

  header('Location: index.php');
  exit;
}

$searchQuery = '';
if (isset($_GET['q'])) {
  $searchQuery = trim((string)$_GET['q']);
}

$filterCategory = '';
if (isset($_GET['filter_category'])) {
  $filterCategory = trim((string)$_GET['filter_category']);
}

$filterSkill = '';
if (isset($_GET['filter_skill'])) {
  $filterSkill = trim((string)$_GET['filter_skill']);
}

$projects = $ideaC->searchProjects($searchQuery, $filterCategory, $filterSkill);
$filterCategories = $ideaC->listProjectCategories();
$filterSkills = $ideaC->listSkillsNames();

$editId = 0;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
}

$editProject = null;
$editSkills = [];
$editMaterials = [];
if ($editId > 0 && $currentUserId > 0) {
  $editProject = $ideaC->showProject($editId);
  if ($editProject) {
    $editSkills = $ideaC->getSkillsForProject($editId);
    $editMaterials = $ideaC->getMaterialsForProject($editId);
  }
}

$formTitle = 'Publier une idee';
$formAction = 'index.php';
$formActionType = 'save';
$formButton = 'Ajouter';
$formClass = 'quick-create card-surface';
if ($editProject) {
  $formTitle = 'Modifier une idee';
  $formActionType = 'update';
  $formButton = 'Enregistrer';
  $formClass = 'quick-create card-surface is-open';
}

$titleValue = '';
$categoryValue = '';
$statusValue = '';
$budgetValue = '';
$descriptionValue = '';
if ($editProject) {
  if (isset($editProject['titre'])) {
    $titleValue = $editProject['titre'];
  }
  if (isset($editProject['categorie'])) {
    $categoryValue = $editProject['categorie'];
  }
  if (isset($editProject['status'])) {
    $statusValue = $editProject['status'];
  }
  if (isset($editProject['budget_min'])) {
    $budgetValue = $editProject['budget_min'];
  }
  if (isset($editProject['description'])) {
    $descriptionValue = $editProject['description'];
  }
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function status_class($status)
{
    $normalized = strtolower((string)$status);
    if ($normalized === 'ouvert') {
        return 'status-open';
    }
    if ($normalized === 'en cours') {
        return 'status-mid';
    }
    if ($normalized === 'ferme') {
        return 'status-closed';
    }
    return 'status-open';
}

$hasProjects = count($projects) > 0;
$emptyStateStyle = '';
if ($hasProjects) {
  $emptyStateStyle = 'display:none;';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>7erfa Tunisie - Idees de projets</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="grain"></div>
  <header class="topbar">
    <div class="brand-block">
      <div class="brand-mark">
        <img src="assets/images/logo.png" alt="Logo 7erfa Tunisie">
      </div>
      <div>
        <p class="brand-name">7erfa Tunisie</p>
        <p class="brand-tagline">L'artisanat tunisien, a l'ere du numerique.</p>
      </div>
    </div>

    <nav class="top-menu" aria-label="Menu principal">
      <button class="menu-link active" type="button">Idee de projet</button>
      <button class="menu-link" type="button">Mon profil</button>
      <button class="menu-link" type="button">Formation</button>
      <button class="menu-link" type="button">Offre d'emploi</button>
    </nav>

    <button class="cta-outline" id="publishIdeaBtn" type="button">+ Publier une idee</button>
    <?php if ($currentUserId > 0): ?>
      <a class="cta-outline" href="logout.php">Deconnexion</a>
    <?php else: ?>
      <a class="cta-outline" href="login.php">Connexion</a>
    <?php endif; ?>
  </header>

  <main class="layout">
    <section class="hero card-surface">
      <div class="hero-copy">
        <p class="eyebrow">Plateforme idees artisanales</p>
        <h1>Lancez une idee claire, trouvez les bonnes competences, avancez vite.</h1>
        <p>
          Creez votre fiche projet en quelques champs: categorie, statut, budget, competences et materiaux.
          Publiez ensuite pour partager votre besoin avec la communaute.
        </p>
        <div class="hero-actions">
          <button class="cta-solid" id="heroPublishBtn" type="button">Commencer maintenant</button>
          <button class="cta-ghost" type="button">Explorer les idees</button>
        </div>
      </div>
      <div class="hero-metrics">
        <article>
          <p class="metric-value">120+</p>
          <p class="metric-label">Idees partagees</p>
        </article>
        <article>
          <p class="metric-value">48</p>
          <p class="metric-label">Artisans actifs</p>
        </article>
        <article>
          <p class="metric-value">17</p>
          <p class="metric-label">Categories en demande</p>
        </article>
      </div>
    </section>

    <section class="<?php echo $formClass; ?>" id="quickCreateSection">
      <div class="quick-create-head">
        <h2><?php echo e($formTitle); ?></h2>
        <?php if ($editProject): ?>
          <a class="mini-btn" href="index.php">Annuler</a>
        <?php else: ?>
          <button class="mini-btn" id="closeFormBtn" type="button">Fermer</button>
        <?php endif; ?>
      </div>
      <h2>Competences et materiaux</h2>
      <form class="idea-form" method="post" action="<?php echo e($formAction); ?>">
        <input type="hidden" name="action" value="<?php echo e($formActionType); ?>">
        <?php if ($editProject): ?>
          <input type="hidden" name="id" value="<?php echo e($editProject['id_projet']); ?>">
        <?php endif; ?>
        <label class="wide">
          Titre
          <input type="text" id="titleInput" name="title" placeholder="Titre du projet" value="<?php echo e($titleValue); ?>">
        </label>

        <label class="wide">
          Categorie
          <select id="categorySelect" name="category">
            <option value="">Choisir une categorie</option>
            <option <?php if ($categoryValue === 'Textile') { echo 'selected'; } ?>>Textile</option>
            <option <?php if ($categoryValue === 'Ceramique') { echo 'selected'; } ?>>Ceramique</option>
            <option <?php if ($categoryValue === 'Bijouterie') { echo 'selected'; } ?>>Bijouterie</option>
            <option <?php if ($categoryValue === 'Menuiserie') { echo 'selected'; } ?>>Menuiserie</option>
            <option <?php if ($categoryValue === 'Autres') { echo 'selected'; } ?>>Autres</option>
          </select>
        </label>

        <label class="wide">
          Statut
          <select id="statusSelect" name="status">
            <option value="">Choisir un statut</option>
            <option <?php if ($statusValue === 'Ouvert') { echo 'selected'; } ?>>Ouvert</option>
            <option <?php if ($statusValue === 'Ferme') { echo 'selected'; } ?>>Ferme</option>
            <option <?php if ($statusValue === 'En cours') { echo 'selected'; } ?>>En cours</option>
          </select>
        </label>

        <label class="wide">
          Budget minimum
          <input type="number" id="budgetInput" name="budget" placeholder="Budget en dinars" min="0" step="0.01" value="<?php echo e($budgetValue); ?>">
        </label>

        <label class="wide">
          Description
          <textarea id="descriptionInput" name="description" placeholder="Decrivez votre projet..."><?php echo e($descriptionValue); ?></textarea>
        </label>

        <label class="wide">
          Competences
          <div class="list-builder" id="skillsBuilder">
            <div class="builder-head">
              <p>Ajoutez le nom et le niveau de chaque competence.</p>
              <button type="button" class="mini-btn" id="addSkillBtn">+ Ajouter competence</button>
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
                    <button type="button" class="row-remove">Retirer</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </label>

        <label class="wide">
          Materiaux
          <div class="list-builder" id="materialsBuilder">
            <div class="builder-head">
              <p>Ajoutez le nom, la quantite et le prix unitaire de chaque materiau.</p>
              <button type="button" class="mini-btn" id="addMaterialBtn">+ Ajouter materiau</button>
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
                    <button type="button" class="row-remove">Retirer</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </label>

        <button class="cta-ghost wide" id="analyseAiBtn" type="button">Analyser avec IA</button>
        <div class="feedback wide" id="aiFeedback" aria-live="polite"></div>
        <section class="ai-result wide" id="aiResult" aria-live="polite"></section>

        <button class="cta-solid wide" type="submit"><?php echo e($formButton); ?></button>
      </form>
    </section>

    <section class="explore" id="ideesProjet">
      <div class="explore-head">
        <div>
          <h2>Idees recentes</h2>
          <p class="explore-subtitle">Vos publications apparaissent ici sous forme de cartes.</p>
        </div>
        <form class="explore-tools" id="projectFilterForm" method="get" action="index.php#ideesProjet" aria-label="Recherche et filtres">
          <input type="search" id="projectSearchInput" name="q" value="<?php echo e($searchQuery); ?>" placeholder="Rechercher une idee..." aria-label="Rechercher une idee">
          <select id="projectCategoryFilter" name="filter_category" aria-label="Filtrer par categorie">
            <option value="">Toutes les categories</option>
            <?php foreach ($filterCategories as $categoryFilterOption): ?>
              <option value="<?php echo e($categoryFilterOption); ?>" <?php if ($filterCategory === $categoryFilterOption) { echo 'selected'; } ?>><?php echo e($categoryFilterOption); ?></option>
            <?php endforeach; ?>
          </select>
          <select id="projectSkillFilter" name="filter_skill" aria-label="Filtrer par competence">
            <option value="">Toutes les competences</option>
            <?php foreach ($filterSkills as $skillFilterOption): ?>
              <option value="<?php echo e($skillFilterOption); ?>" <?php if ($filterSkill === $skillFilterOption) { echo 'selected'; } ?>><?php echo e($skillFilterOption); ?></option>
            <?php endforeach; ?>
          </select>
          <button class="mini-btn" id="projectFilterBtn" type="submit">Filtrer</button>
          <a class="mini-btn" href="index.php#ideesProjet">Reinitialiser</a>
        </form>
      </div>
      <div class="content-grid">
        <div class="cards" id="projectCards">
          <?php foreach ($projects as $project): ?>
            <?php
              $projectId = 0;
              if (isset($project['id_projet'])) {
                $projectId = (int)$project['id_projet'];
              }

              $skillsRows = [];
              $materialRows = [];
              if ($projectId > 0) {
                $skillsRows = $ideaC->getSkillsForProject($projectId);
                $materialRows = $ideaC->getMaterialsForProject($projectId);
              }

              $skillText = 'Aucune competence';
              $skillsFilterValues = [];
              if (count($skillsRows) > 0) {
                  $skillText = $skillsRows[0]['nom'];
                  if (!empty($skillsRows[0]['skill_level'])) {
                      $skillText .= ' (' . $skillsRows[0]['skill_level'] . ')';
                  }

                  foreach ($skillsRows as $skillRow) {
                    if (!empty($skillRow['nom'])) {
                      $skillsFilterValues[] = trim((string)$skillRow['nom']);
                    }
                  }
              }

              $skillsFilterValue = implode('|', $skillsFilterValues);

              $materialText = 'Aucun materiau';
              $qtyText = 'Quantite';
              if (count($materialRows) > 0) {
                  $materialText = $materialRows[0]['nom_materiel'];
                  if (!empty($materialRows[0]['quantite'])) {
                      $qtyText = $materialRows[0]['quantite'] . ' pcs';
                  }
              }

              $titleDisplay = 'Projet sans titre';
              if (isset($project['titre']) && $project['titre'] !== '') {
                $titleDisplay = $project['titre'];
              }

              $categoryDisplay = 'Non specifiee';
              if (isset($project['categorie']) && $project['categorie'] !== '') {
                $categoryDisplay = $project['categorie'];
              }

              $statusDisplay = 'Ajoute';
              if (isset($project['status']) && $project['status'] !== '') {
                $statusDisplay = $project['status'];
              }

              $descDisplay = 'Aucune description';
              if (isset($project['description']) && $project['description'] !== '') {
                $descDisplay = $project['description'];
              }

              $budgetDisplay = 'Budget non defini';
              if ($project['budget_min'] !== null && $project['budget_min'] !== '') {
                  $budgetDisplay = number_format((float)$project['budget_min'], 0, ',', ' ') . ' DT';
              }

              $isOwner = false;
              if (isset($project['id_user']) && (int)$project['id_user'] === (int)$currentUserId) {
                $isOwner = true;
              }
            ?>
            <article class="project-card card-surface" data-title="<?php echo e($titleDisplay); ?>" data-desc="<?php echo e($descDisplay); ?>" data-category="<?php echo e($categoryDisplay); ?>" data-skills="<?php echo e($skillsFilterValue); ?>">
              <div class="card-top">
                <p class="card-status <?php echo e(status_class($statusDisplay)); ?>"><?php echo e($statusDisplay); ?></p>
                <p class="card-budget"><?php echo e($budgetDisplay); ?></p>
              </div>
              <h3><?php echo e($titleDisplay); ?></h3>
              <p class="card-category"><?php echo e($categoryDisplay); ?></p>
              <p class="card-desc"><?php echo e($descDisplay); ?></p>
              <div class="tags">
                <span><?php echo e($skillText); ?></span>
                <span><?php echo e($materialText); ?></span>
                <span><?php echo e($qtyText); ?></span>
              </div>
              <div class="card-meta">
                <span>Entree locale</span>
                <div class="card-actions">
                  <button class="join-btn" type="button">Voir</button>
                  <?php if ($isOwner): ?>
                    <a class="mini-btn" href="index.php?edit=<?php echo e($project['id_projet']); ?>#quickCreateSection" onclick="return confirm('Voulez-vous modifier cette idee ?');">Editer</a>
                    <form method="post" action="index.php" class="inline-form" onsubmit="return confirm('Voulez-vous supprimer cette idee ?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo e($project['id_projet']); ?>">
                      <button class="row-remove" type="submit">Supprimer</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <aside class="details card-surface">
          <h3>Conseils de publication</h3>
          <p class="details-text">
            Soyez precis sur le besoin et le budget pour recevoir des reponses utiles plus rapidement.
          </p>
          <div class="details-list">
            <p><strong>Titre:</strong> court et explicite</p>
            <p><strong>Competences:</strong> 2 a 4 competences cles</p>
            <p><strong>Materiaux:</strong> quantite + prix unitaire</p>
            <p><strong>Description:</strong> objectif, delai, contraintes</p>
          </div>
          <div class="details-cta">
            <button class="cta-solid" id="asidePublishBtn" type="button">Publier une idee</button>
          </div>
        </aside>
      </div>
      <div class="empty-state card-surface" id="emptyState" style="<?php echo $emptyStateStyle; ?>">
        <h3>Aucune idee pour le moment</h3>
        <p>Utilisez le bouton "Publier une idee" pour ajouter votre premiere carte.</p>
      </div>
    </section>

    <section class="info-section card-surface" id="monProfil">
      <div class="info-head">
        <h2>Mon profil</h2>
      </div>
      <p>
        Ajoutez ici vos informations principales: metier, experience, competences, ville et disponibilite.
      </p>
    </section>

    <section class="info-section card-surface" id="formations">
      <div class="info-head">
        <h2>Formation</h2>
      </div>
      <p>
        Retrouvez des formations courtes pour textile, ceramique, business artisanal et marketing digital.
      </p>
    </section>

    <section class="info-section card-surface" id="offresEmploi">
      <div class="info-head">
        <h2>Offre d'emploi</h2>
      </div>
      <p>
        Consultez les offres locales et les besoins de recrutement publies par les entrepreneurs et ateliers.
      </p>
    </section>
  </main>
  <script src="assets/js/script.js"></script>
</body>
</html>
