-- ============================================================================
-- 📋 MIGRATION SQL COMPLÈTE - Nouvelles Fonctionnalités de Profil Utilisateur
-- ============================================================================
-- Date : 2026-04-23
-- Statut : Production-ready
-- Description : Ajout des colonnes pour la gestion du profil et du blocage
--
-- COLONNES AJOUTÉES :
--   1. date_naissance    - Date de naissance de l'utilisateur
--   2. statut_marital    - Statut marital (célibataire, marié, etc.)
--   3. is_blocked        - Booléen pour le blocage d'compte par l'admin
--   4. date_modification - Timestamp de dernière modification
-- ============================================================================

-- ⚠️ IMPORTANT : Exécuter ce script dans la base de données : craftlink_db

USE craftlink_db;

-- ============================================================================
-- ✅ ÉTAPE 1 : Vérifier la structure actuelle de la table user
-- ============================================================================
-- Décommentez pour voir la structure :
-- DESCRIBE user;
-- SHOW COLUMNS FROM user;

-- ============================================================================
-- ✅ ÉTAPE 2 : Ajouter les colonnes manquantes
-- ============================================================================

-- Colonne 1 : Date de naissance
-- Utilise ALTER IGNORE TABLE pour éviter les doublons
ALTER TABLE user ADD COLUMN IF NOT EXISTS date_naissance DATE NULL DEFAULT NULL AFTER etat_compte;

-- Colonne 2 : Statut marital (ENUM avec valeurs prédéfinies)
ALTER TABLE user ADD COLUMN IF NOT EXISTS statut_marital 
  ENUM('celibataire', 'marie', 'divorce', 'veuf', 'autre') 
  NULL DEFAULT NULL AFTER date_naissance;

-- Colonne 3 : Blocage du compte par l'admin (BOOLEAN = TINYINT(1))
ALTER TABLE user ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE AFTER statut_marital;

-- Colonne 4 : Date de dernière modification (suivi des changements)
ALTER TABLE user ADD COLUMN IF NOT EXISTS date_modification 
  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER is_blocked;

-- ============================================================================
-- ✅ ÉTAPE 3 : Vérifier que les colonnes sont bien ajoutées
-- ============================================================================
-- Décommentez pour vérifier :
-- SELECT * FROM user LIMIT 1;
-- SHOW COLUMNS FROM user WHERE Field IN ('date_naissance','statut_marital','is_blocked','date_modification');

-- ============================================================================
-- ✅ ÉTAPE 4 : Créer des index pour améliorer les performances
-- ============================================================================

-- Index pour les recherches sur le blocage
CREATE INDEX IF NOT EXISTS idx_is_blocked ON user(is_blocked);

-- Index pour les recherches chronologiques
CREATE INDEX IF NOT EXISTS idx_date_modification ON user(date_modification);

-- Index composite pour recherche et blocage
CREATE INDEX IF NOT EXISTS idx_email_blocked ON user(email, is_blocked);

-- ============================================================================
-- ✅ ÉTAPE 5 : Mettre à jour les valeurs par défaut (optionnel)
-- ============================================================================

-- Initialiser tous les comptes existants comme non bloqués
UPDATE user SET is_blocked = FALSE WHERE is_blocked IS NULL;

-- ============================================================================
-- ✅ ÉTAPE 6 : Vérifier les données
-- ============================================================================

-- Voir tous les utilisateurs avec les nouveaux champs
SELECT 
    id_user,
    nom,
    prenom,
    email,
    role,
    etat_compte,
    date_naissance,
    statut_marital,
    is_blocked,
    date_modification,
    date_creation
FROM user
ORDER BY date_creation DESC;

-- Compter les utilisateurs bloqués
-- SELECT COUNT(*) as users_blocked FROM user WHERE is_blocked = TRUE;

-- Lister les utilisateurs bloqués
-- SELECT id_user, nom, prenom, email, is_blocked, date_modification FROM user WHERE is_blocked = TRUE;

-- ============================================================================
-- ✅ ÉTAPE 7 : Requêtes de Test (décommentez pour tester)
-- ============================================================================

-- Test 1 : Ajouter une date de naissance et un statut marital à un utilisateur
-- UPDATE user SET date_naissance = '1990-05-15', statut_marital = 'marie' WHERE id_user = 1;

-- Test 2 : Bloquer un utilisateur
-- UPDATE user SET is_blocked = TRUE WHERE id_user = 2;

-- Test 3 : Débloquer un utilisateur
-- UPDATE user SET is_blocked = FALSE WHERE id_user = 2;

-- Test 4 : Voir la date de modification (AUTO UPDATE)
-- SELECT id_user, nom, date_modification FROM user WHERE id_user = 1;

-- ============================================================================
-- ⚠️ NOTES IMPORTANTES
-- ============================================================================
-- 
-- 1. Cette migration est REVERSIBLE :
--    Pour revenir en arrière, exécutez :
--      ALTER TABLE user DROP COLUMN IF EXISTS date_naissance;
--      ALTER TABLE user DROP COLUMN IF EXISTS statut_marital;
--      ALTER TABLE user DROP COLUMN IF EXISTS is_blocked;
--      ALTER TABLE user DROP COLUMN IF EXISTS date_modification;
--
-- 2. Les migrations PHPMyAdmin peuvent être lentes sur de grandes tables
--    Si vous avez beaucoup d'utilisateurs, utilisez mysql directement en ligne de commande
--
-- 3. Sauvegardez toujours votre base de données avant les migrations !
--    mysqldump -u root -p craftlink_db > backup_craftlink_$(date +%Y%m%d_%H%M%S).sql
--
-- 4. La synchronisation Supabase se fera automatiquement depuis PHP
--    Pas besoin de modifier les tables Supabase manuellement
--
-- ============================================================================
-- ✅ SUCCÈS : Migration complétée !
-- ============================================================================
-- Si vous avez atteint ce point sans erreurs, la migration a réussi.
-- Les nouvelles colonnes sont maintenant disponibles pour votre application.
