<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo = getPDO();
    
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $specialite = $_POST['specialite'];
    $bio = $_POST['bio'];
    $portfolio = $_POST['portfolio'];
    $experience = $_POST['experience'];
    $ville = $_POST['ville'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $email = $_POST['email'] ?? '';
    $id = $_SESSION['user_id'];

    try {
        // Mettre à jour user
        $sql1 = "UPDATE user SET nom=?, prenom=?, email=?, telephone=? WHERE id_user=?";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$nom, $prenom, $email, $telephone, $id]);
        
        // Vérifier si profil existe
        $check = $pdo->prepare("SELECT id_profil FROM profil_professionnel WHERE id_user = ?");
        $check->execute([$id]);
        
        if ($check->fetch()) {
            $sql2 = "UPDATE profil_professionnel SET specialite=?, bio=?, portfolio=?, experience=?, ville=? WHERE id_user=?";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$specialite, $bio, $portfolio, $experience, $ville, $id]);
        } else {
            $sql2 = "INSERT INTO profil_professionnel (id_user, specialite, bio, portfolio, experience, ville, date_creation) VALUES (?, ?, ?, ?, ?, ?, CURDATE())";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$id, $specialite, $bio, $portfolio, $experience, $ville]);
        }
        
        header("Location: profil_complet.php?success=1");
        exit();
    } catch (PDOException $e) {
        header("Location: profil_complet.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}
?>