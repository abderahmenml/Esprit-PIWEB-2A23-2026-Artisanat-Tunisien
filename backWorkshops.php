<?php
require_once 'config.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function certifOui($certification)
{
    $c = strtolower(trim((string) $certification));

    if ($c === 'oui' || $c === 'yes' || $c === '1') {
        return true;
    }

    return false;
}

function workshopStatutClass($statut)
{
    $s = strtolower(trim((string) $statut));

    if ($s === 'en_cours') {
        return 'badge-actif';
    }
    if ($s === 'termine') {
        return 'badge-termine';
    }
    if ($s === 'annule') {
        return 'badge-inactif';
    }

    return 'badge-brouillon';
}

function workshopStatutLabel($statut)
{
    $s = strtolower(trim((string) $statut));

    if ($s === 'a_venir') {
        return 'À venir';
    }
    if ($s === 'en_cours') {
        return 'En cours';
    }
    if ($s === 'termine') {
        return 'Terminé';
    }
    if ($s === 'annule') {
        return 'Annulé';
    }

    return 'Non défini';
}

function workshopDureeLabel($duree)
{
    if (!is_numeric($duree)) {
        return 'Non défini';
    }

    $d = (int) $duree;

    if ($d <= 0) {
        return 'Non défini';
    }

    return (string) $d . 'h';
}

function workshopMentorLabel($mentorNom, $mentorId)
{
    $nom = trim((string) $mentorNom);

    if ($nom !== '') {
        return $nom;
    }

    if (is_numeric($mentorId) && (int) $mentorId > 0) {
        return 'Utilisateur #' . (string) ((int) $mentorId);
    }

    return 'Non assigné';
}

function workshopDescriptionCourte($description)
{
    $texte = trim((string) $description);

    if ($texte === '') {
        return 'Description indisponible.';
    }

    if (strlen($texte) <= 95) {
        return $texte;
    }

    return substr($texte, 0, 92) . '...';
}

$erreur = '';
$succes = '';

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $succes = 'Workshop supprimé avec succès.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

    if ($action === 'delete') {
        $idWorkshop = 0;
        if (isset($_POST['id_workshop'])) {
            $idWorkshop = (int) $_POST['id_workshop'];
        }

        if ($idWorkshop <= 0) {
            $erreur = 'Identifiant de workshop invalide.';
        }

        if ($erreur === '') {
            try {
                $sqlDelete = 'DELETE FROM workshops WHERE id_workshop = :id';
                $stmtDelete = $pdo->prepare($sqlDelete);
                $stmtDelete->bindValue(':id', $idWorkshop, PDO::PARAM_INT);
                $stmtDelete->execute();

                header('Location: backWorkshops.php?deleted=1');
                exit;
            } catch (PDOException $e) {
                $erreur = 'Erreur base de données pendant la suppression.';
            }
        }
    }
}

$recherche = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$dureeFiltre = isset($_GET['duree']) ? trim((string) $_GET['duree']) : '';
$statutFiltre = isset($_GET['statut']) ? trim((string) $_GET['statut']) : '';
$certifFiltre = isset($_GET['certif']) ? trim((string) $_GET['certif']) : '';

if ($dureeFiltre !== '2h' && $dureeFiltre !== 'journee') {
    $dureeFiltre = '';
}

$statutsAutorises = ['a_venir', 'en_cours', 'termine', 'annule'];
if (!in_array($statutFiltre, $statutsAutorises, true)) {
    $statutFiltre = '';
}

if ($certifFiltre !== 'oui' && $certifFiltre !== 'non') {
    $certifFiltre = '';
}

$workshops = [];

try {
    $sql = 'SELECT w.id_workshop, w.titre, w.description, w.mentor_id, w.duree, w.lieu, w.places_max, w.places_restantes, w.prix, w.certification, w.statut,
                   TRIM(CONCAT(COALESCE(u.prenom, ""), " ", COALESCE(u.nom, ""))) AS mentor_nom,
                   (SELECT COUNT(*) FROM inscriptions_workshop iw WHERE iw.id_workshop = w.id_workshop) AS inscrits
            FROM workshops w
            LEFT JOIN `user` u ON u.id_user = w.mentor_id';

    $conditions = [];
    $params = [];

    if ($recherche !== '') {
        $conditions[] = '(w.titre LIKE :q OR w.description LIKE :q OR w.lieu LIKE :q OR TRIM(CONCAT(COALESCE(u.prenom, ""), " ", COALESCE(u.nom, ""))) LIKE :q)';
        $params[':q'] = '%' . $recherche . '%';
    }

    if ($dureeFiltre === '2h') {
        $conditions[] = 'w.duree < 4';
    }
    if ($dureeFiltre === 'journee') {
        $conditions[] = 'w.duree >= 4';
    }

    if ($statutFiltre !== '') {
        $conditions[] = 'w.statut = :statut';
        $params[':statut'] = $statutFiltre;
    }

    if ($certifFiltre !== '') {
        $conditions[] = 'w.certification = :certif';
        $params[':certif'] = $certifFiltre;
    }

    if (count($conditions) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY w.id_workshop DESC';

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $workshops = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Erreur lors du chargement des workshops.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BackOffice Workshops | CraftLink</title>
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
.btn-link {
  text-decoration: none;
}
.badge-termine {
  background: rgba(139,90,58,0.12);
  color: var(--marron);
}
.small-muted {
  color: var(--gris);
  font-size: 0.75rem;
}
@media (max-width: 900px) {
  .toolbar {
    align-items: stretch;
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
    <a class="nav-item" href="back.php"><span class="icon">📜</span> Formations</a>
    <a class="nav-item active" href="backWorkshops.php"><span class="icon">🎓</span> Workshops</a>
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
      Modules &rsaquo; <strong>Workshops (base de données)</strong>
    </div>
    <div class="topbar-actions simple-actions">
      <a class="btn-primary btn-link" href="frontWorkshops.html">Voir le site</a>
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
      <h2>Liste des workshops</h2>
    </div>

    <form method="get" action="backWorkshops.php" class="toolbar">
      <div class="toolbar-search">
        <input type="text" name="q" value="<?php echo e($recherche); ?>" placeholder="Rechercher par titre, lieu, mentor, description...">
      </div>
      <select name="duree">
        <option value="">Toutes durées</option>
        <option value="2h" <?php echo ($dureeFiltre === '2h' ? 'selected' : ''); ?>>Moins de 3h</option>
        <option value="journee" <?php echo ($dureeFiltre === 'journee' ? 'selected' : ''); ?>>Journée (4h+)</option>
      </select>
      <select name="statut">
        <option value="">Tous statuts</option>
        <option value="a_venir" <?php echo ($statutFiltre === 'a_venir' ? 'selected' : ''); ?>>À venir</option>
        <option value="en_cours" <?php echo ($statutFiltre === 'en_cours' ? 'selected' : ''); ?>>En cours</option>
        <option value="termine" <?php echo ($statutFiltre === 'termine' ? 'selected' : ''); ?>>Terminé</option>
        <option value="annule" <?php echo ($statutFiltre === 'annule' ? 'selected' : ''); ?>>Annulé</option>
      </select>
      <select name="certif">
        <option value="">Certif ou non</option>
        <option value="oui" <?php echo ($certifFiltre === 'oui' ? 'selected' : ''); ?>>Avec certification</option>
        <option value="non" <?php echo ($certifFiltre === 'non' ? 'selected' : ''); ?>>Sans certification</option>
      </select>
      <button type="submit" class="btn-primary">Filtrer</button>
      <a href="backWorkshops.php" class="btn-primary btn-link">Réinitialiser</a>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Atelier</th>
            <th>Mentor</th>
            <th>Durée</th>
            <th>Lieu</th>
            <th>Places</th>
            <th>Inscriptions</th>
            <th>Certif.</th>
            <th>Statut</th>
            <th>Prix</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($workshops) === 0): ?>
            <tr>
              <td colspan="11" style="text-align:center;padding:20px;">Aucun workshop trouvé.</td>
            </tr>
          <?php else: ?>
            <?php for ($i = 0; $i < count($workshops); $i++): ?>
              <?php
                $w = $workshops[$i];
                $mentorAffiche = workshopMentorLabel($w['mentor_nom'], $w['mentor_id']);
                $descriptionCourte = workshopDescriptionCourte($w['description']);
                $statutClass = workshopStatutClass($w['statut']);
                $statutLabel = workshopStatutLabel($w['statut']);
                $certif = certifOui($w['certification']);
                $placesMax = is_numeric($w['places_max']) ? (int) $w['places_max'] : 0;
                $placesRestantes = is_numeric($w['places_restantes']) ? (int) $w['places_restantes'] : 0;
                $inscrits = is_numeric($w['inscrits']) ? (int) $w['inscrits'] : 0;
                $prix = is_numeric($w['prix']) ? (float) $w['prix'] : 0;
              ?>
              <tr>
                <td>#<?php echo e((string) $w['id_workshop']); ?></td>
                <td>
                  <strong><?php echo e($w['titre']); ?></strong><br>
                  <span class="small-muted"><?php echo e($descriptionCourte); ?></span>
                </td>
                <td><?php echo e($mentorAffiche); ?></td>
                <td><?php echo e(workshopDureeLabel($w['duree'])); ?></td>
                <td><?php echo e((string) $w['lieu']); ?></td>
                <td><?php echo e((string) $placesRestantes); ?> / <?php echo e((string) $placesMax); ?></td>
                <td><?php echo e((string) $inscrits); ?></td>
                <td><?php echo ($certif ? '✅' : '—'); ?></td>
                <td>
                  <span class="badge <?php echo e($statutClass); ?>"><?php echo e($statutLabel); ?></span>
                </td>
                <td><?php echo e(number_format($prix, 2, '.', '')); ?> TND</td>
                <td>
                  <form method="post" action="backWorkshops.php" onsubmit="return confirm('Supprimer ce workshop ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_workshop" value="<?php echo e((string) $w['id_workshop']); ?>">
                    <button type="submit" class="btn-action btn-suppr">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endfor; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="pagination">
        <span><?php echo e((string) count($workshops)); ?> workshop(s)</span>
      </div>
    </div>
  </div>
</div>
</body>
</html>
