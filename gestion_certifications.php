<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$certifications = getUserCertifications($user_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des certifications</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Gestion des certifications</h1>
<a href="profil_professionnel.php">➕ Ajouter une certification</a>
<table border="1" cellpadding="8" style="margin-top:20px;">
    <tr><th>Ordre</th><th>Nom</th><th>Niveau</th><th>Actions</th></tr>
    <?php foreach ($certifications as $cert): ?>
    <tr>
        <td><?= (int)($cert['ordre'] ?? 0) ?></td>
        <td><?= htmlspecialchars($cert['nom_certification'] ?? '') ?></td>
        <td><?= (int)($cert['niveau'] ?? 0) ?>%</td>
        <td>
            <form action="delete_certification.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette certification ?');">
                <input type="hidden" name="id" value="<?= (int)($cert['id_certification'] ?? 0) ?>">
                <button type="submit" style="color:red;">🗑️</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
