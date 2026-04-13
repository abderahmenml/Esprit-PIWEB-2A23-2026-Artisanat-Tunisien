<?php
// Fichier de fonctions utilitaires pour le projet CraftLink

function getUserById($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStats($user_id) {
    $pdo = getPDO();
    try {
        // Count projects
        $stmtProjects = $pdo->prepare("SELECT COUNT(*) as count FROM projet WHERE id_user = ?");
        $stmtProjects->execute([$user_id]);
        $projets = (int)$stmtProjects->fetchColumn();
        
        // Get average rating from avis
        $stmtRating = $pdo->prepare("SELECT AVG(note) as note FROM avis WHERE id_user_recepteur = ?");
        $stmtRating->execute([$user_id]);
        $note = $stmtRating->fetchColumn() ?: 0.0;
        
        // Count mentored users
        $stmtMentores = $pdo->prepare("SELECT COUNT(*) as count FROM application_offre WHERE id_mentor = ? AND statut = 'accepte'");
        $stmtMentores->execute([$user_id]);
        $mentores = (int)$stmtMentores->fetchColumn();
        
        return [
            'projets' => $projets,
            'note' => number_format((float)$note, 1),
            'mentores' => $mentores
        ];
    } catch (Exception $e) {
        return ['projets' => 0, 'note' => '0.0', 'mentores' => 0];
    }
}

function getUserCompetences($user_id) {
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT id_competence, nom_competence, description, niveau, ordre FROM competences WHERE id_user = ? ORDER BY ordre ASC, id_competence ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getUserCertifications($user_id) {
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT id_certification, nom_certification, niveau, ordre FROM certification WHERE id_user = ? ORDER BY ordre ASC, id_certification ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getUserPortfolioFiles($user_id) {
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT id_portfolio_file, titre, file_name, file_path, created_at FROM portfolio_files WHERE id_user = ? ORDER BY created_at DESC, id_portfolio_file DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getUserExperiences($user_id) {
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT * FROM experience WHERE id_user = ? ORDER BY date_debut DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getUserAvis($user_id) {
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT avis.*, user.prenom, user.nom FROM avis 
                             JOIN user ON avis.id_user_auteur = user.id_user 
                             WHERE avis.id_user_recepteur = ? 
                             ORDER BY avis.date_avis DESC 
                             LIMIT 5");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}
