-- ═══════════════════════════════════════════════════════════════════════════
-- CraftLink DB — Mise à jour pour architecture MVC
-- Ajoute un compte admin pour accéder au BackOffice
-- ═══════════════════════════════════════════════════════════════════════════

-- Ajouter le champ 'admin' comme rôle possible (déjà géré en PHP)
-- Pas de modification de schéma nécessaire, le champ role est VARCHAR(50)

-- Créer un compte administrateur
-- Mot de passe : Admin@2026  (hashé avec password_hash en PHP)
INSERT INTO `user` (`nom`, `prenom`, `email`, `mot_de_passe`, `role`, `date_creation`, `etat_compte`)
VALUES (
  'Admin',
  'CraftLink',
  'admin@craftlink.tn',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- mot de passe: "password" (CHANGER EN PROD!)
  'admin',
  CURDATE(),
  'actif'
)
ON DUPLICATE KEY UPDATE role = 'admin', etat_compte = 'actif';

-- Pour créer un vrai hash, exécutez ce PHP une fois :
-- echo password_hash('VotreMotDePasse', PASSWORD_DEFAULT);
-- Puis remplacez le hash ci-dessus.

-- Vérification
SELECT id_user, nom, prenom, email, role, etat_compte FROM user;
