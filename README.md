# HERFA Tunisie

Plateforme web dédiée à l’artisanat tunisien: projets, formations, offres d’emploi, candidatures, dashboard recruteur et administration (vérification des offres).

## ✅ État actuel

Le projet est fonctionnel avec:
- Authentification par rôles (`artisan`, `recruteur`, `entrepreneur`, `admin`)
- Gestion des offres d’emploi
- Candidature artisan avec upload CV
- Dashboard recruteur (offres + candidatures)
- Dashboard admin (modération + vérification des offres)
- Schéma base de données aligné avec le code

## 🧱 Stack technique

- PHP 8.x (XAMPP)
- MariaDB / MySQL
- HTML/CSS/JS (Bootstrap)
- Architecture mixte MVC (controllers / views / models)

## 📁 Structure (principale)

- `controllers/` logique routes/flux
- `views/frontend/` pages utilisateur
- `views/backend/` pages recruteur/admin
- `models/` accès données métier
- `config/` configuration/app bootstrap
- `public/assets/` CSS/JS/images
- `uploads/` fichiers uploadés (CV, images offres)
- `database/migrations/` scripts SQL et export DB

## 🚀 Installation locale (XAMPP)

1. Placer le projet dans `C:\xampp\htdocs\herfa`
2. Démarrer Apache + MySQL dans XAMPP
3. Créer la base `herfa_tunisie`
4. Importer l’export SQL actuel:
   - `database/migrations/herfa_tunisie.sql`
5. Vérifier les permissions d’écriture:
   - `uploads/`
   - `uploads/cv/`
6. Ouvrir:
   - `http://localhost/herfa/controllers/home.php`

## 🗄️ Base de données

Le dump courant contient:
- `offre_emploi` avec cycle de vie + vérification (`verification_status`, `verified_at`, `verified_by`, `moderation_note`)
- `application` avec statuts compatibles (`pending`, `accepted`, `rejected` + historiques)
- table pivot `application_offre` restaurée
- index/FK nécessaires au dashboard admin/recruteur

## 👥 Rôles et navigation

- **Artisan**: consulter offres, postuler, voir mes candidatures
- **Recruteur/Entrepreneur**: créer/éditer offres, voir candidatures reçues
- **Admin**: vérifier/dé-vérifier les offres, modérer et contrôler les statuts

## 🔐 Sécurité en place

- Contrôles de rôle côté serveur
- CSRF sur actions sensibles (offres/candidatures/modération)
- Validation upload fichiers (taille/type)
- Requêtes préparées PDO

## 🧪 Vérification rapide

- Lint PHP:
  - `C:\xampp\php\php.exe -l <fichier.php>`
- Pages clés:
  - Home
  - Offres
  - Postuler
  - Dashboard recruteur
  - Dashboard admin

## 📝 TODO (Roadmap)

- [ ] Unifier complètement toutes les URLs legacy restantes vers un helper unique
- [ ] Nettoyer les vues dupliquées encore héritées de l’ancien flux
- [ ] Ajouter un système de logs admin (qui a modéré quoi, quand)
- [ ] Ajouter pagination serveur sur toutes les listes admin/recruteur
- [ ] Ajouter tests d’intégration (auth, offres, candidature, modération)
- [ ] Mettre en place un script de migration versionné (ordre + rollback)
- [ ] Ajouter validation plus stricte des extensions CV et antivirus (optionnel)
- [ ] Ajouter gestion d’erreurs utilisateur plus propre (flash messages globaux)
- [ ] Ajouter README API interne (routes + paramètres)
- [ ] Préparer version production (env, cache, hardening, backups)

## 🤝 Contribution

1. Créer une branche feature
2. Commits clairs et atomiques
3. Lancer lint/tests locaux
4. Ouvrir une Pull Request

## 📌 Notes

- Ne pas exécuter automatiquement les anciens scripts de nettoyage non révisés.
- Toujours faire un backup DB avant migration.
