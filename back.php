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

function niveauBadgeClass($niveau)
{
    $n = normalizeNiveau($niveau);

    if ($n === 'debutant') {
        return 'badge-debutant';
    }
    if ($n === 'intermediaire') {
        return 'badge-inter';
    }
    if ($n === 'avance') {
        return 'badge-avance';
    }

    return 'badge-brouillon';
}

function statutBadgeClass($etat)
{
    $s = strtolower(trim((string) $etat));

    if ($s === 'actif') {
        return 'badge-actif';
    }
    if ($s === 'inactif') {
        return 'badge-inactif';
    }

    return 'badge-brouillon';
}

function certifOui($certification)
{
    $c = strtolower(trim((string) $certification));

    if ($c === 'oui' || $c === 'yes' || $c === '1') {
        return true;
    }

    return false;
}

$erreur = '';
$succes = '';

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
  $succes = 'Formation supprimée avec succès.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';

  if ($action === 'delete') {
    $idFormation = 0;
    if (isset($_POST['id_formation'])) {
      $idFormation = (int) $_POST['id_formation'];
    }

    if ($idFormation <= 0) {
      $erreur = 'Identifiant de formation invalide.';
    }

    if ($erreur === '') {
      try {
        $sqlDelete = 'DELETE FROM formations WHERE id_formation = :id';
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->bindValue(':id', $idFormation, PDO::PARAM_INT);
        $stmtDelete->execute();

        header('Location: back.php?deleted=1');
        exit;
      } catch (PDOException $e) {
        $erreur = 'Erreur base de données pendant la suppression.';
      }
    }
  }
}

$recherche = isset($_GET['q']) ? trim($_GET['q']) : '';
$niveauFiltre = isset($_GET['niveau']) ? trim($_GET['niveau']) : '';
$niveauFiltreDb = '';

if ($niveauFiltre !== '') {
    $niveauFiltreDb = normalizeNiveau($niveauFiltre);
}

$sql = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix,
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

if (count($conditions) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY f.id_formation DESC';

$formations = [];

try {
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $formations = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Erreur lors du chargement des formations.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BackOffice Formations | CraftLink</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Calibri:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="backoffice.css">
<style>
.inline-alert {
  margin-bottom: 16px;
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
.simple-panel {
  background: var(--blanc);
  border: 1px solid rgba(196,154,108,0.2);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 18px;
  box-shadow: 0 1px 8px rgba(59,35,20,0.05);
}
.simple-panel h3 {
  font-family: 'Playfair Display', serif;
  margin-bottom: 14px;
}
.simple-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.btn-link {
  text-decoration: none;
}
@media (max-width: 800px) {
  .form-row-2,
  .form-row-3 {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo">
    <h1>ح CraftLink</h1>
    <p>Administration</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Modules</div>
    <a class="nav-item active" href="back.php"><span class="icon">📜</span> Formations</a>
    <a class="nav-item" href="backWorkshops.php"><span class="icon">🎓</span> Workshops</a>
    <a class="nav-item" href="front.php"><span class="icon">🌐</span> Front Formations</a>
    <a class="nav-item" href="frontWorkshops.html"><span class="icon">🏺</span> Front Workshops</a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar">AD</div>
      <div>
        <div class="admin-name">Admin CraftLink</div>
        <div class="admin-role">Super Administrateur</div>
      </div>
    </div>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="breadcrumb">
      Modules &rsaquo; <strong>Formations (base de données)</strong>
    </div>
    <div class="topbar-actions simple-actions">
      <a class="btn-primary btn-link" href="front.php">Voir le site</a>
    </div>
  </div>

  <div class="content">
    <?php if ($erreur !== ''): ?>
      <div class="inline-alert error"><?php echo e($erreur); ?></div>
    <?php endif; ?>

    <?php if ($succes !== ''): ?>
      <div class="inline-alert success"><?php echo e($succes); ?></div>
    <?php endif; ?>

    <div class="section-head">
      <h2>Liste des formations</h2>
    </div>

    <form method="get" action="back.php" class="toolbar">
      <div class="toolbar-search">
        <input type="text" name="q" value="<?php echo e($recherche); ?>" placeholder="Rechercher par titre, mentor, description...">
      </div>
      <select name="niveau">
        <option value="">Tous niveaux</option>
        <option value="debutant" <?php echo ($niveauFiltreDb === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
        <option value="intermediaire" <?php echo ($niveauFiltreDb === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
        <option value="avance" <?php echo ($niveauFiltreDb === 'avance' ? 'selected' : ''); ?>>Avancé</option>
      </select>
      <button type="submit" class="btn-primary">Filtrer</button>
      <a href="back.php" class="btn-primary btn-link">Réinitialiser</a>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Mentor</th>
            <th>Niveau</th>
            <th>Durée</th>
            <th>Inscrits</th>
            <th>Certif.</th>
            <th>Statut</th>
            <th>Prix</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($formations) === 0): ?>
            <tr>
              <td colspan="10" style="text-align:center;padding:20px;">Aucune formation trouvée.</td>
            </tr>
          <?php else: ?>
            <?php for ($i = 0; $i < count($formations); $i++): ?>
              <?php $f = $formations[$i]; ?>
              <tr>
                <td>#<?php echo e($f['id_formation']); ?></td>
                <td><strong><?php echo e($f['domaine']); ?></strong></td>
                <td><?php echo e($f['formateur']); ?></td>
                <td>
                  <span class="badge <?php echo e(niveauBadgeClass($f['niveau'])); ?>"><?php echo e(niveauLabel($f['niveau'])); ?></span>
                </td>
                <td><?php echo e((string) $f['duree']); ?>h</td>
                <td><?php echo e((string) $f['inscrits']); ?></td>
                <td><?php echo (certifOui($f['certification']) ? '✅' : '—'); ?></td>
                <td>
                  <span class="badge <?php echo e(statutBadgeClass($f['etat'])); ?>"><?php echo e((string) $f['etat']); ?></span>
                </td>
                <td><?php echo e((string) $f['prix']); ?> TND</td>
                <td>
                  <form method="post" action="back.php" onsubmit="return confirm('Supprimer cette formation ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_formation" value="<?php echo e((string) $f['id_formation']); ?>">
                    <button type="submit" class="btn-action btn-suppr">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endfor; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="pagination">
        <span><?php echo e((string) count($formations)); ?> formation(s)</span>
      </div>
    </div>
  </div>
</div>
</body>
</html>
