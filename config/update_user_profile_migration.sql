-- Migration : Ajouter les colonnes pour le profil utilisateur et le blocage

-- Vérifier et ajouter date_naissance
ALTER TABLE user ADD COLUMN date_naissance DATE NULL AFTER etat_compte;

-- Vérifier et ajouter statut_marital
ALTER TABLE user ADD COLUMN statut_marital ENUM('celibataire', 'marie', 'divorce', 'veuf', 'autre') NULL AFTER date_naissance;

-- Vérifier et ajouter is_blocked pour la gestion du blocage par l'admin
ALTER TABLE user ADD COLUMN is_blocked BOOLEAN DEFAULT FALSE AFTER statut_marital;

-- Vérifier et ajouter date_modification pour suivre les mises à jour
ALTER TABLE user ADD COLUMN date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER is_blocked;
