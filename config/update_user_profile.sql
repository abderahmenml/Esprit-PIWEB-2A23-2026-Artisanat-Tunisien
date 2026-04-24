-- Migration: Ajouter les colonnes du profil utilisateur
-- Date: 2024

ALTER TABLE `user` 
ADD COLUMN `date_naissance` DATE NULL DEFAULT NULL AFTER `etat_compte`,
ADD COLUMN `num_carte_identite` VARCHAR(20) NULL DEFAULT NULL AFTER `date_naissance`,
ADD COLUMN `statut_marie` ENUM('celibataire', 'marie', 'divorce', 'veuf') NULL DEFAULT NULL AFTER `num_carte_identite`,
ADD COLUMN `telephone` VARCHAR(20) NULL DEFAULT NULL AFTER `statut_marie`,
ADD COLUMN `adresse` TEXT NULL DEFAULT NULL AFTER `telephone`,
ADD COLUMN `photo_profil` VARCHAR(255) NULL DEFAULT NULL AFTER `adresse`;
