<?php
require __DIR__ . '/db.php';

$projects = [];
try {
    $projects = $pdo->query(
        'SELECT id_projet, titre, budget_min, status, date_creation, categorie, description FROM projet ORDER BY id_projet DESC'
    )->fetchAll();
} catch (Throwable $e) {
    $projects = [];
}

$skillStmt = $pdo->prepare(
    'SELECT s.nom, s.`level` AS skill_level FROM required_skills rs JOIN skills s ON s.id = rs.id_skill WHERE rs.id_projet = ?'
);
$materialStmt = $pdo->prepare(
    'SELECT m.nom_materiel, rm.quantite, rm.prix_unitaire FROM required_mater rm JOIN materiel m ON m.id_materiel = rm.id_materiel WHERE rm.id_projet = ?'
);

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
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="grain"></div>
  <header class="topbar">
    <div class="brand-block">
      <div class="brand-mark">
        <img src="image/logo.png" alt="Logo 7erfa Tunisie">
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

    <section class="quick-create card-surface" id="quickCreateSection">
      <div class="quick-create-head">
        <h2>Publier une idee</h2>
        <button class="mini-btn" id="closeFormBtn" type="button">Fermer</button>
      </div>
      <h2>Competences et materiaux</h2>
      <form class="idea-form" method="post" action="save_idea.php">
        <label class="wide">
          Titre
          <input type="text" id="titleInput" name="title" placeholder="Titre du projet">
        </label>

        <label class="wide">
          Categorie
          <select id="categorySelect" name="category">
            <option value="">Choisir une categorie</option>
            <option>Textile</option>
            <option>Ceramique</option>
            <option>Bijouterie</option>
            <option>Menuiserie</option>
            <option>Autres</option>
          </select>
        </label>

        <label class="wide">
          Statut
          <select id="statusSelect" name="status">
            <option value="">Choisir un statut</option>
            <option>Ouvert</option>
            <option>Ferme</option>
            <option>En cours</option>
          </select>
        </label>

        <label class="wide">
          Budget minimum
          <input type="number" id="budgetInput" name="budget" placeholder="Budget en dinars" min="0" step="0.01">
        </label>

        <label class="wide">
          Description
          <textarea id="descriptionInput" name="description" placeholder="Decrivez votre projet..."></textarea>
        </label>

        <label class="wide">
          Competences
          <div class="list-builder" id="skillsBuilder">
            <div class="builder-head">
              <p>Ajoutez le nom et le niveau de chaque competence.</p>
              <button type="button" class="mini-btn" id="addSkillBtn">+ Ajouter competence</button>
            </div>
            <div id="skillsRows" class="builder-rows"></div>
          </div>
        </label>

        <label class="wide">
          Materiaux
          <div class="list-builder" id="materialsBuilder">
            <div class="builder-head">
              <p>Ajoutez le nom, la quantite et le prix unitaire de chaque materiau.</p>
              <button type="button" class="mini-btn" id="addMaterialBtn">+ Ajouter materiau</button>
            </div>
            <div id="materialsRows" class="builder-rows"></div>
          </div>
        </label>

        <button class="cta-solid wide" type="submit">Ajouter</button>
      </form>
    </section>

    <section class="explore" id="ideesProjet">
      <div class="explore-head">
        <div>
          <h2>Idees recentes</h2>
          <p class="explore-subtitle">Vos publications apparaissent ici sous forme de cartes.</p>
        </div>
      </div>
      <div class="content-grid">
        <div class="cards" id="projectCards">
          <?php foreach ($projects as $project): ?>
            <?php
              $skillStmt->execute([$project['id_projet']]);
              $skillsRows = $skillStmt->fetchAll();

              $materialStmt->execute([$project['id_projet']]);
              $materialRows = $materialStmt->fetchAll();

              $skillText = 'Aucune competence';
              if (count($skillsRows) > 0) {
                  $skillText = $skillsRows[0]['nom'];
                  if (!empty($skillsRows[0]['skill_level'])) {
                      $skillText .= ' (' . $skillsRows[0]['skill_level'] . ')';
                  }
              }

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
            ?>
            <article class="project-card card-surface">
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
                <button class="join-btn" type="button">Voir</button>
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
  <script src="script.js"></script>
</body>
</html>
