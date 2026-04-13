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

$editId = 0;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
}
$editProject = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id_projet, titre, budget_min, status, categorie, description FROM projet WHERE id_projet = ?');
    $stmt->execute([$editId]);
    $editProject = $stmt->fetch();
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
  <link rel="stylesheet" href="backoffice.css">
</head>
<body>
  <header class="bo-topbar">
    <div>
      <h1>Backoffice - Idees</h1>
      <p>Gestion simple depuis la base de donnees.</p>
    </div>
    <a class="btn" href="index.php">Retour site</a>
  </header>

  <main class="bo-layout">
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
        <form method="post" action="update_idea.php" class="edit-form">
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

          <div class="form-actions">
            <a class="btn" href="backoffice.php">Annuler</a>
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
                <td colspan="7">Aucune idee pour le moment.</td>
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
                ?>
                <tr>
                  <td><?php echo e($project['id_projet']); ?></td>
                  <td><?php echo e($project['titre']); ?></td>
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
                    <a class="btn small" href="backoffice.php?edit=<?php echo e($project['id_projet']); ?>">Editer</a>
                    <form method="post" action="delete_idea.php" class="inline-form">
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
  </main>
</body>
</html>
