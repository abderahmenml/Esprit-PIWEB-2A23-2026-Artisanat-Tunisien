<?php
require_once 'config.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeNiveau($niveau)
{
    $n = trim((string) $niveau);

    if ($n === 'Débutant' || $n === 'debutant' || $n === 'débutant') {
        return 'debutant';
    }
    if ($n === 'Intermédiaire' || $n === 'intermediaire' || $n === 'intermediere' || $n === 'intermédiaire') {
        return 'intermediaire';
    }
    if ($n === 'Avancé' || $n === 'avance' || $n === 'avancé') {
        return 'avance';
    }

    return '';
}

function niveauLabel($niveau)
{
    $n = normalizeNiveau($niveau);

    if ($n === 'debutant') {
        return 'Débutant';
    }
    if ($n === 'intermediaire') {
        return 'Intermédiaire';
    }
    if ($n === 'avance') {
        return 'Avancé';
    }

    return 'Non défini';
}

function certifOui($certification)
{
    $c = strtolower(trim((string) $certification));

    if ($c === 'oui' || $c === 'yes' || $c === '1') {
        return true;
    }

    return false;
}

function initials($name)
{
    $clean = trim((string) $name);
    if ($clean === '') {
        return '??';
    }

    $parts = explode(' ', $clean);
    $letters = '';

    for ($i = 0; $i < count($parts); $i++) {
        $part = trim($parts[$i]);
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
            if (strlen($letters) === 2) {
                break;
            }
        }
    }

    if ($letters === '') {
        $letters = strtoupper(substr($clean, 0, 2));
    }

    return $letters;
}

$erreur = '';
$succes = '';
$openFormModal = false;

$old = [
  'titre' => '',
  'description' => '',
  'mentor' => '',
  'niveau' => 'debutant',
  'duree' => '',
  'prix' => '',
  'certification' => 'oui',
  'etat' => 'Actif',
  'date_realisation' => ''
];

if (isset($_GET['ok']) && $_GET['ok'] === '1') {
  $succes = 'Formation ajoutée avec succès.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $openFormModal = true;

  $old['titre'] = isset($_POST['titre']) ? trim($_POST['titre']) : '';
  $old['description'] = isset($_POST['description']) ? trim($_POST['description']) : '';
  $old['mentor'] = isset($_POST['mentor']) ? trim($_POST['mentor']) : '';
  $old['niveau'] = isset($_POST['niveau']) ? trim($_POST['niveau']) : 'debutant';
  $old['duree'] = isset($_POST['duree']) ? trim($_POST['duree']) : '';
  $old['prix'] = isset($_POST['prix']) ? trim($_POST['prix']) : '';
  $old['certification'] = isset($_POST['certification']) ? trim($_POST['certification']) : 'oui';
  $old['etat'] = isset($_POST['etat']) ? trim($_POST['etat']) : 'Actif';
  $old['date_realisation'] = isset($_POST['date_realisation']) ? trim($_POST['date_realisation']) : '';

  if ($old['titre'] === '' || strlen($old['titre']) < 3) {
    $erreur = 'Le titre doit contenir au moins 3 caractères.';
  }

  if ($erreur === '' && $old['mentor'] === '') {
    $erreur = 'Le mentor est obligatoire.';
  }

  $niveauDb = normalizeNiveau($old['niveau']);
  if ($erreur === '' && $niveauDb === '') {
    $erreur = 'Le niveau sélectionné est invalide.';
  }

  if ($erreur === '' && ($old['duree'] === '' || !ctype_digit($old['duree']) || (int) $old['duree'] <= 0)) {
    $erreur = 'La durée doit être un nombre entier supérieur à 0.';
  }

  if ($erreur === '' && !is_numeric($old['prix'])) {
    $erreur = 'Le prix doit être un nombre valide.';
  }

  if ($erreur === '') {
    $dateRealisation = null;
    if ($old['date_realisation'] !== '') {
      $dateRealisation = $old['date_realisation'];
    }

    try {
      $sqlInsert = 'INSERT INTO formations (domaine, formateur, description, date_realisation, etat, duree, certification, niveau, prix)
              VALUES (:domaine, :formateur, :description, :date_realisation, :etat, :duree, :certification, :niveau, :prix)';

      $stmtInsert = $pdo->prepare($sqlInsert);
      $stmtInsert->bindValue(':domaine', $old['titre']);
      $stmtInsert->bindValue(':formateur', $old['mentor']);
      $stmtInsert->bindValue(':description', $old['description']);

      if ($dateRealisation === null) {
        $stmtInsert->bindValue(':date_realisation', null, PDO::PARAM_NULL);
      } else {
        $stmtInsert->bindValue(':date_realisation', $dateRealisation);
      }

      $stmtInsert->bindValue(':etat', $old['etat']);
      $stmtInsert->bindValue(':duree', (int) $old['duree'], PDO::PARAM_INT);
      $stmtInsert->bindValue(':certification', $old['certification']);
      $stmtInsert->bindValue(':niveau', $niveauDb);
      $stmtInsert->bindValue(':prix', (float) $old['prix']);
      $stmtInsert->execute();

      header('Location: front.php?ok=1');
      exit;
    } catch (PDOException $e) {
      $erreur = 'Erreur base de données pendant l\'ajout.';
    }
  }
}

$recherche = isset($_GET['q']) ? trim($_GET['q']) : '';
$niveauFiltre = isset($_GET['niveau']) ? trim($_GET['niveau']) : '';
$certifFiltre = isset($_GET['certif']) ? trim($_GET['certif']) : '';

$niveauFiltreDb = '';
if ($niveauFiltre !== '') {
  $niveauFiltreDb = normalizeNiveau($niveauFiltre);
}

$formations = [];
$totalFormations = 0;
$totalInscrits = 0;
$mentorsUniques = [];

try {
    $sqlStats = 'SELECT f.id_formation, f.formateur,
                        (SELECT COUNT(*) FROM inscriptions i WHERE i.id_formation = f.id_formation) AS inscrits
                 FROM formations f';
    $stmtStats = $pdo->prepare($sqlStats);
    $stmtStats->execute();
    $statsRows = $stmtStats->fetchAll();

    $totalFormations = count($statsRows);

    for ($i = 0; $i < count($statsRows); $i++) {
        $row = $statsRows[$i];
        $totalInscrits += (int) $row['inscrits'];

        $mentor = trim((string) $row['formateur']);
        if ($mentor !== '' && !in_array($mentor, $mentorsUniques, true)) {
            $mentorsUniques[] = $mentor;
        }
    }

    $sql = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.duree, f.certification, f.niveau, f.prix,
                   (SELECT COUNT(*) FROM inscriptions i WHERE i.id_formation = f.id_formation) AS inscrits
            FROM formations f';

    $conditions = [];
    $params = [];

    if ($recherche !== '') {
        $conditions[] = '(f.domaine LIKE :q OR f.formateur LIKE :q OR f.description LIKE :q)';
        $params[':q'] = '%' . $recherche . '%';
    }

    if ($niveauFiltreDb !== '') {
        $conditions[] = 'f.niveau = :niveau';
        $params[':niveau'] = $niveauFiltreDb;
    }

    if ($certifFiltre === 'oui' || $certifFiltre === 'non') {
        $conditions[] = 'f.certification = :certif';
        $params[':certif'] = $certifFiltre;
    }

    if (count($conditions) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY f.id_formation DESC';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $formations = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Impossible de charger les formations depuis la base.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Formations CraftLink (Base de données)</title>
<link href="https://fonts.googleapis.com/css2?family=Georgia&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Calibri:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.inline-alert {
  max-width: 1100px;
  margin: 16px auto 0;
  padding: 10px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
}
.inline-alert.error {
  background: rgba(192, 57, 43, 0.12);
  color: #7b1f15;
  border: 1px solid rgba(192, 57, 43, 0.35);
}
.inline-alert.success {
  background: rgba(46, 107, 62, 0.12);
  color: #1f4d2b;
  border: 1px solid rgba(46, 107, 62, 0.35);
}
.filter-form {
  max-width: 1100px;
  margin: 0 auto;
  padding: 10px 32px 0;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.filter-form select {
  padding: 8px 12px;
  border-radius: 20px;
  border: 1.5px solid var(--caramel);
  color: var(--marron);
  background: transparent;
}
.filter-form .btn-inscrit {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}
.filter-actions-right {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
}
.filter-actions-right .btn-inscrit {
  white-space: nowrap;
}
.front-form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.front-form-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.btn-cancel {
  border: 1px solid rgba(196,154,108,0.5);
  color: var(--gris);
  background: transparent;
  border-radius: 6px;
  padding: 9px 14px;
  cursor: pointer;
}
.btn-save {
  border: none;
  color: var(--creme);
  background: var(--marron);
  border-radius: 6px;
  padding: 9px 14px;
  cursor: pointer;
}
.btn-save:hover {
  background: var(--brun);
}
@media (max-width: 800px) {
  .filter-actions-right {
    margin-left: 0;
    width: 100%;
    justify-content: flex-end;
  }
  .front-form-row-2,
  .front-form-row-3 {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<nav>
  <div class="logo">
    ح <span>CraftLink Tunisie</span>
  </div>
  <ul class="nav-links">
    <li><a href="frontWorkshops.html" id="workshopsTabLink">Workshops</a></li>
    <li><a href="front.php" class="active" id="formationsTabLink">Formations</a></li>
    <li><a href="back.php">Admin</a></li>
  </ul>
</nav>

<section class="hero">
  <h1>Formations depuis la <em>base de données</em></h1>
  <p>Chaque formation affichée ici vient de la table formations. Vous pouvez aussi ajouter une formation directement depuis cette page.</p>
  <form method="get" action="front.php" class="search-bar">
    <input type="text" name="q" value="<?php echo e($recherche); ?>" placeholder="Rechercher une formation...">
    <button type="submit">Rechercher</button>
  </form>
</section>

<div class="stats-bar">
  <div class="stat"><strong><?php echo e((string) $totalFormations); ?></strong><span>Formations dans la base</span></div>
  <div class="stat"><strong><?php echo e((string) count($mentorsUniques)); ?></strong><span>Mentors</span></div>
  <div class="stat"><strong><?php echo e((string) $totalInscrits); ?>+</strong><span>Inscriptions</span></div>
  <div class="stat"><strong><?php echo e((string) count($formations)); ?></strong><span>Résultats affichés</span></div>
</div>

<div class="filter-section">
  <span class="filter-label">Filtrer la liste :</span>
</div>
<form method="get" action="front.php" class="filter-form">
  <input type="hidden" name="q" value="<?php echo e($recherche); ?>">
  <select name="niveau">
    <option value="">Tous niveaux</option>
    <option value="debutant" <?php echo ($niveauFiltreDb === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
    <option value="intermediaire" <?php echo ($niveauFiltreDb === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
    <option value="avance" <?php echo ($niveauFiltreDb === 'avance' ? 'selected' : ''); ?>>Avancé</option>
  </select>
  <select name="certif">
    <option value="">Certif ou non</option>
    <option value="oui" <?php echo ($certifFiltre === 'oui' ? 'selected' : ''); ?>>Avec certification</option>
    <option value="non" <?php echo ($certifFiltre === 'non' ? 'selected' : ''); ?>>Sans certification</option>
  </select>
  <button type="submit" class="btn-inscrit">Appliquer</button>
  <a href="front.php" class="btn-inscrit vert">Réinitialiser</a>
  <div class="filter-actions-right">
    <button type="button" class="btn-inscrit" id="btnAjouterFormationFrontPhp" onclick="ouvrirFormAjoutFront()">＋ Ajouter une formation</button>
  </div>
</form>

<?php if ($succes !== ''): ?>
  <div class="inline-alert success"><?php echo e($succes); ?></div>
<?php endif; ?>

<?php if ($erreur !== ''): ?>
  <div class="inline-alert error"><?php echo e($erreur); ?></div>
<?php endif; ?>

<div class="section-title container">Formations disponibles</div>
<div class="berber-divider container">◆ ◇ ◆ ◇ ◆</div>

<div class="cards-wrapper">
  <div class="cards-grid" id="cardsGrid">
    <?php if (count($formations) === 0): ?>
      <div class="card">
        <div class="card-body">
          <h3>Aucune formation trouvée</h3>
          <p>Essayez un autre filtre ou ajoutez une nouvelle formation via le bouton Ajouter une formation.</p>
        </div>
      </div>
    <?php else: ?>
      <?php for ($i = 0; $i < count($formations); $i++): ?>
        <?php
          $f = $formations[$i];
          $niveau = niveauLabel($f['niveau']);
          $certif = certifOui($f['certification']);
          $description = trim((string) $f['description']);
          if ($description === '') {
              $description = 'Description bientôt disponible.';
          }
          $avatar = initials($f['formateur']);
        ?>
        <div class="card">
          <div class="card-body">
            <div class="card-meta">
              <span class="tag tag-niveau"><?php echo e($niveau); ?></span>
              <?php if ($certif): ?>
                <span class="tag tag-certif">📜 Certifiant</span>
              <?php endif; ?>
            </div>
            <h3><?php echo e($f['domaine']); ?></h3>
            <p><?php echo e($description); ?></p>
            <div class="card-info">
              <span>🕐 <?php echo e((string) $f['duree']); ?>h</span>
              <span>👤 <?php echo e((string) $f['inscrits']); ?> inscrits</span>
            </div>
          </div>
          <div class="card-mentor">
            <div class="mentor-avatar" style="background:var(--marron)"><?php echo e($avatar); ?></div>
            <div class="mentor-info">
              <strong><?php echo e($f['formateur']); ?></strong>
              <small>Tunisie</small>
            </div>
          </div>
          <div class="card-footer">
            <div class="price"><?php echo e((string) $f['prix']); ?> TND
              <small><?php echo ($certif ? 'Certificat inclus' : 'Attestation de suivi'); ?></small>
            </div>
            <a class="btn-inscrit<?php echo ($certif ? '' : ' vert'); ?>" href="front.php">S'inscrire</a>
          </div>
        </div>
      <?php endfor; ?>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay<?php echo ($openFormModal ? ' open' : ''); ?>" id="modalAjoutFront" onclick="fermerFormFrontSiExterieur(event)">
  <div class="modal" style="max-width:760px;">
    <div class="modal-header">
      <div>
        <h2>Ajouter une formation</h2>
        <p>Les données seront enregistrées dans la base</p>
      </div>
      <button class="modal-close" type="button" onclick="fermerFormAjoutFront()">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" action="front.php">
        <div class="front-form-row-2">
          <div class="form-group">
            <label>Titre *</label>
            <input type="text" name="titre" value="<?php echo e($old['titre']); ?>" placeholder="Ex: Initiation à la poterie">
          </div>
          <div class="form-group">
            <label>Mentor *</label>
            <input type="text" name="mentor" value="<?php echo e($old['mentor']); ?>" placeholder="Ex: Fatma Ayari">
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="3" placeholder="Description de la formation"><?php echo e($old['description']); ?></textarea>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Niveau *</label>
            <select name="niveau">
              <option value="debutant" <?php echo ($old['niveau'] === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
              <option value="intermediaire" <?php echo ($old['niveau'] === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
              <option value="avance" <?php echo ($old['niveau'] === 'avance' ? 'selected' : ''); ?>>Avancé</option>
            </select>
          </div>
          <div class="form-group">
            <label>Durée (heures) *</label>
            <input type="text" name="duree" value="<?php echo e($old['duree']); ?>" placeholder="Ex: 24">
          </div>
          <div class="form-group">
            <label>Prix (TND) *</label>
            <input type="text" name="prix" value="<?php echo e($old['prix']); ?>" placeholder="Ex: 180">
          </div>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Certification</label>
            <select name="certification">
              <option value="oui" <?php echo ($old['certification'] === 'oui' ? 'selected' : ''); ?>>Oui</option>
              <option value="non" <?php echo ($old['certification'] === 'non' ? 'selected' : ''); ?>>Non</option>
            </select>
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select name="etat">
              <option value="Actif" <?php echo ($old['etat'] === 'Actif' ? 'selected' : ''); ?>>Actif</option>
              <option value="Inactif" <?php echo ($old['etat'] === 'Inactif' ? 'selected' : ''); ?>>Inactif</option>
              <option value="Brouillon" <?php echo ($old['etat'] === 'Brouillon' ? 'selected' : ''); ?>>Brouillon</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date de réalisation</label>
            <input type="date" name="date_realisation" value="<?php echo e($old['date_realisation']); ?>">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="fermerFormAjoutFront()">Annuler</button>
          <button type="submit" class="btn-save">💾 Enregistrer dans la base</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer>
  <strong>ح CraftLink Tunisie</strong> · Données réelles depuis MySQL
</footer>
<script>
function ouvrirFormAjoutFront()
{
  var modal = document.getElementById('modalAjoutFront');
  if (modal) {
    modal.classList.add('open');
  }
}

function fermerFormAjoutFront()
{
  var modal = document.getElementById('modalAjoutFront');
  if (modal) {
    modal.classList.remove('open');
  }
}

function fermerFormFrontSiExterieur(event)
{
  if (event.target && event.target.id === 'modalAjoutFront') {
    fermerFormAjoutFront();
  }
}

document.addEventListener('DOMContentLoaded', function ()
{
  var boutonAjout = document.getElementById('btnAjouterFormationFrontPhp');
  if (boutonAjout) {
    boutonAjout.addEventListener('click', function (event) {
      event.preventDefault();
      ouvrirFormAjoutFront();
    });
  }
});
</script>
</body>
</html>
