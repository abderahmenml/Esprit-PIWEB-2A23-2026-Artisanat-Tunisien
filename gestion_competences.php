<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$competences = getUserCompetences($user_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des compétences</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Gestion des compétences</h1>
<a href="profil_professionnel.php">➕ Ajouter une compétence</a>
<table border="1" cellpadding="8" style="margin-top:20px;">
    <tr><th>Ordre</th><th>Nom</th><th>Description</th><th>Niveau</th><th>Actions</th></tr>
    <?php foreach ($competences as $c): ?>
    <tr>
        <td><?= (int)$c['ordre'] ?></td>
        <td><?= htmlspecialchars($c['nom_competence']) ?></td>
        <td><?= htmlspecialchars($c['description'] ?? '') ?></td>
        <td><?= (int)$c['niveau'] ?>%</td>
        <td>
            <form action="delete_competance.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette competence ?');">
                <input type="hidden" name="id" value="<?= (int)$c['id_competence'] ?>">
                <button type="submit" style="color:red;">🗑️</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<!-- TODO: Ajout drag & drop pour réordonner -->
</body>
</html>
