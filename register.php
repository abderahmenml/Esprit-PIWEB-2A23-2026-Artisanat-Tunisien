<?php
session_start();
require_once 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pdo = getPDO();
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'candidat';

    // Vérifier unicité email
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Cet email est déjà utilisé.";
    } elseif (!$nom || !$prenom || !$email || !$password) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $sql = "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte) VALUES (?, ?, ?, ?, ?, CURDATE(), 'actif')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom, $prenom, $email, $hash, $role]);
            // Connexion automatique
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_role'] = $role;
            header("Location: profil_professionnel.php");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>
<form action="" method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="prenom" placeholder="Prénom" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <select name="role">
        <option value="candidat">Candidat</option>
        <option value="recruteur">Recruteur</option>
    </select>
    <button type="submit">S'inscrire</button>
    <?php if (!empty($error)): ?>
        <div style="color:#b00; margin-top:10px; text-align:center;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
</form>