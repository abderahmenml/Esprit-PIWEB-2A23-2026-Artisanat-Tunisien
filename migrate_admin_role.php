<?php
/**
 * Script de migration pour corriger le rôle admin
 * Exécutez une seule fois: http://localhost/craftlink/migrate_admin_role.php
 */

require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "<h1>Migration: Correction du rôle Admin</h1>";

    // Vérifier si l'admin existe
    $stmt = $pdo->prepare("SELECT id_user, role FROM user WHERE email = ?");
    $stmt->execute(['admin@craftlink.tn']);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "<p style='color:blue'>✓ Admin n'existe pas. Création...</p>";
        
        $stmt = $pdo->prepare("
            INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            'Admin',
            'CraftLink',
            'admin@craftlink.tn',
            '$2y$10$dG1oNk/B6807L5lBLgd9IO1ZTL4gkNTz0TuOqcUCqy0daTzlPhDfK', // Admin@2026
            'admin',
            'actif'
        ]);
        
        $id = $pdo->lastInsertId();
        echo "<p style='color:green'>✓ Admin créé avec succès (ID: $id)</p>";
    } elseif ($admin['role'] !== 'admin') {
        echo "<p style='color:orange'>⚠ Admin existe mais rôle incorrect: {$admin['role']}</p>";
        
        $stmt = $pdo->prepare("UPDATE user SET role = ? WHERE id_user = ?");
        $stmt->execute(['admin', $admin['id_user']]);
        
        echo "<p style='color:green'>✓ Rôle corrigé en 'admin'</p>";
    } else {
        echo "<p style='color:green'>✓ Admin existe déjà avec le bon rôle</p>";
    }

    // Vérifier que tout est bon
    $stmt = $pdo->prepare("SELECT id_user, nom, prenom, email, role, etat_compte FROM user WHERE email = ?");
    $stmt->execute(['admin@craftlink.tn']);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<h2>État final de l'admin:</h2>";
        echo "<pre>";
        echo "ID: {$admin['id_user']}\n";
        echo "Nom: {$admin['prenom']} {$admin['nom']}\n";
        echo "Email: {$admin['email']}\n";
        echo "Rôle: {$admin['role']}\n";
        echo "État: {$admin['etat_compte']}\n";
        echo "Mot de passe: Admin@2026\n";
        echo "</pre>";
        
        if ($admin['role'] === 'admin' && $admin['etat_compte'] === 'actif') {
            echo "<p style='color:green;font-size:1.2em'><strong>✓ SUCCÈS - Vous pouvez maintenant vous connecter!</strong></p>";
        }
    } else {
        echo "<p style='color:red'>✗ Erreur: Admin non trouvé après migration</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur base de données: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit(1);
}

echo "<hr>";
echo "<p><a href='index.php?page=login'>← Retour à la page de connexion</a></p>";
?>
