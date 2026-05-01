-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2026 at 06:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `herfa_tunisie`
--

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_offer` int(11) NOT NULL,
  `lettre_de_motivation` text NOT NULL,
  `cv` text NOT NULL,
  `status` enum('pending','submitted','reviewed','shortlisted','interview','accepted','rejected') NOT NULL DEFAULT 'pending',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `parsed_cv_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parsed_cv_data`)),
  `cv_parsing_status` enum('pending','parsing','success','failed','manual_entry') DEFAULT 'pending',
  `cv_parsed_at` datetime DEFAULT NULL,
  `cv_file_name` varchar(255) DEFAULT NULL,
  `cv_file_size` int(11) DEFAULT NULL,
  `cv_file_type` varchar(50) DEFAULT NULL,
  `cv_file_hash` varchar(64) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_messages`
--

CREATE TABLE `application_messages` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_offre`
--

CREATE TABLE `application_offre` (
  `id_application` int(11) NOT NULL,
  `id_offre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `artisan_onboarding`
--

CREATE TABLE `artisan_onboarding` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skills` text NOT NULL,
  `interests` text NOT NULL,
  `work_types` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `craft_focus` text DEFAULT NULL,
  `project_interests` text DEFAULT NULL,
  `job_interests` text DEFAULT NULL,
  `formation_interests` text DEFAULT NULL,
  `tools_materials` text DEFAULT NULL,
  `experience_level` varchar(50) DEFAULT NULL,
  `experience_years` varchar(50) DEFAULT NULL,
  `collaboration_styles` text DEFAULT NULL,
  `availability` text DEFAULT NULL,
  `location_preferences` text DEFAULT NULL,
  `market_channels` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artisan_onboarding`
--

INSERT INTO `artisan_onboarding` (`id`, `user_id`, `skills`, `interests`, `work_types`, `completed_at`, `created_at`, `updated_at`, `craft_focus`, `project_interests`, `job_interests`, `formation_interests`, `tools_materials`, `experience_level`, `experience_years`, `collaboration_styles`, `availability`, `location_preferences`, `market_channels`) VALUES
(0, 5, '', '', NULL, NULL, '2026-05-01 17:18:02', '2026-05-01 17:18:02', '', NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `candidate_education`
--

CREATE TABLE `candidate_education` (
  `id` int(11) NOT NULL,
  `id_candidate_profile` int(11) NOT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `field_of_study` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_experience`
--

CREATE TABLE `candidate_experience` (
  `id` int(11) NOT NULL,
  `id_candidate_profile` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_profile`
--

CREATE TABLE `candidate_profile` (
  `id` int(11) NOT NULL,
  `id_application` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `professional_title` varchar(255) DEFAULT NULL,
  `professional_summary` text DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `education` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education`)),
  `work_experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_experience`)),
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certifications`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `profile_completeness` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_skills`
--

CREATE TABLE `candidate_skills` (
  `id` int(11) NOT NULL,
  `id_candidate_profile` int(11) NOT NULL,
  `skill_name` varchar(100) DEFAULT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `years_of_experience` decimal(3,1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certification`
--

CREATE TABLE `certification` (
  `id_certification` int(11) NOT NULL,
  `id_user` bigint(20) NOT NULL,
  `nom_certification` varchar(150) NOT NULL,
  `niveau` tinyint(3) UNSIGNED NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certification`
--

INSERT INTO `certification` (`id_certification`, `id_user`, `nom_certification`, `niveau`, `ordre`) VALUES
(4, 5, 'Certification Automatisation No Code', 90, 1),
(5, 5, 'Certification Marketing Digital', 82, 2),
(6, 5, 'Certification Data Analytics', 78, 3),
(7, 4, 'Front-End Web Development (HTML, CSS, JS)', 54, 1),
(8, 4, 'JavaScript Algorithms and Data Structures', 88, 2),
(9, 4, 'PHP & MySQL Development', 60, 3),
(10, 4, 'UI/UX Design Fundamentals', 7, 4),
(11, 4, 'REST API Development', 100, 5),
(12, 4, 'Git & GitHub Version Control', 0, 6);

-- --------------------------------------------------------

--
-- Table structure for table `competences`
--

CREATE TABLE `competences` (
  `id_competence` int(11) NOT NULL,
  `nom_competence` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `id_user` int(11) DEFAULT 0,
  `nombre_projets` int(11) NOT NULL DEFAULT 0,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp(),
  `niveau` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `id_competence_catalog` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competences`
--

INSERT INTO `competences` (`id_competence`, `nom_competence`, `description`, `id_user`, `nombre_projets`, `date_ajout`, `niveau`, `ordre`, `id_competence_catalog`) VALUES
(1, 'Poterie', 'Fabrication dobjet en cÚramique', 0, 12, '2026-05-01 15:38:26', 0, 0, NULL),
(2, 'Broderie', 'Broderie traditionnelle tunisienne', 0, 8, '2026-05-01 15:38:26', 0, 0, NULL),
(3, 'Bois sculptÚ', 'Travail artisanal du bois', 0, 5, '2026-05-01 15:38:26', 0, 0, NULL),
(4, 'Orchestration Make', 'CrÚation de flux complexes multi-outils avec gestion derreurs.', 0, 0, '2026-05-01 15:38:26', 88, 2, NULL),
(5, 'Prompt Engineering', 'Conception de prompts mÚtier pour assistants IA clients.', 0, 0, '2026-05-01 15:38:26', 84, 3, NULL),
(6, 'SEO local', 'Optimisation de la visibilitÚ locale des ateliers et boutiques.', 0, 0, '2026-05-01 15:38:26', 76, 4, NULL),
(7, 'Poterie traditionnelle', 'Artisanat de la poterie traditionnelle tunisienne', 0, 0, '2026-05-01 15:38:26', 100, 0, NULL),
(8, 'Tressage traditionnel', 'Artisanat du tressage traditionnel', 0, 0, '2026-05-01 15:38:26', 90, 0, NULL),
(9, 'Design artisanal moderne', 'Design appliquÚ Ó lartisanat', 0, 0, '2026-05-01 15:38:26', 75, 0, NULL),
(10, 'Marketing des produits artisanaux', 'Marketing et vente de produits artisanaux', 0, 0, '2026-05-01 15:38:26', 70, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `competence_catalog`
--

CREATE TABLE `competence_catalog` (
  `id_competence_catalog` int(11) NOT NULL,
  `nom_competence` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experience`
--

CREATE TABLE `experience` (
  `id_experience` int(11) NOT NULL,
  `id_user` bigint(20) NOT NULL,
  `poste` varchar(150) NOT NULL,
  `entreprise` varchar(150) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `experience`
--

INSERT INTO `experience` (`id_experience`, `id_user`, `poste`, `entreprise`, `date_debut`, `date_fin`, `description`) VALUES
(4, 5, 'Consultant Automatisation', 'Herfa Studio', '2021-02-01', '2022-12-31', 'Automatisation des workflows de vente et support.'),
(5, 5, 'Workflow Architect', 'Digital Atlas', '2023-01-01', NULL, 'Architecture de solutions IA et no-code pour PMEs.'),
(6, 5, 'Mentor Productivit├®', 'Freelance', '2020-01-10', '2021-01-20', 'Accompagnement d\'├®quipes artisanales sur la transformation digitale.'),
(7, 4, 'AI Workflow Architect', 'Freelance', '2023-01-01', NULL, 'Conception et automatisation de workflows IA pour des clients e-commerce et startups.'),
(8, 4, 'Consultant en Automatisation', 'Herfa Studio', '2022-01-01', '2022-12-31', 'Mise en place de solutions no-code pour optimiser les processus m├®tiers.'),
(9, 4, 'Digital Marketing Assistant', 'Startup Digital X', '2021-06-01', '2021-12-31', 'Gestion des campagnes marketing et analyse des performances.');

-- --------------------------------------------------------

--
-- Table structure for table `formations`
--

CREATE TABLE `formations` (
  `id` int(11) NOT NULL,
  `titre` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `niveau` varchar(60) NOT NULL DEFAULT 'Débutant',
  `prix` decimal(12,2) NOT NULL DEFAULT 0.00,
  `duree` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `formateur` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materiel`
--

CREATE TABLE `materiel` (
  `id_materiel` bigint(20) NOT NULL,
  `nom_materiel` char(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materiel`
--

INSERT INTO `materiel` (`id_materiel`, `nom_materiel`, `description`) VALUES
(1, 'hnrter', NULL),
(2, ',hgzefzef', NULL),
(3, ',hgzefzef', NULL),
(4, ',hgzefzef', NULL),
(5, 'tissue', NULL),
(6, 'hjklm', NULL),
(7, 'gzegz', NULL),
(8, 'htgf', NULL),
(9, 'htgf', NULL),
(10, 'jklm', NULL),
(11, '^fghjklm', NULL),
(12, 'klih', NULL),
(13, 'klih', NULL),
(14, 'klih', NULL),
(15, 'klih', NULL),
(16, 'Feuilles de palmier', NULL),
(17, 'Teintures naturelles', NULL),
(18, 'Fil de couture solide', NULL),
(19, 'Outils artisanaux (ciseaux, aiguilles, presses)', NULL),
(20, 'Emballage écologique', NULL),
(21, 'Transport local', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offer_bookmarks`
--

CREATE TABLE `offer_bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offer_reports`
--

CREATE TABLE `offer_reports` (
  `id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offer_views`
--

CREATE TABLE `offer_views` (
  `id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `viewed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offre_emploi`
--

CREATE TABLE `offre_emploi` (
  `id_offer` int(11) NOT NULL,
  `titre` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `skills_needed` text DEFAULT NULL,
  `budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `duree` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `id_projet` int(11) NOT NULL,
  `id_recruteur` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `verification_status` enum('not_verified','verified') NOT NULL DEFAULT 'not_verified',
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `moderation_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `location` varchar(150) DEFAULT NULL,
  `contact_email` varchar(190) DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT NULL,
  `experience_level` varchar(50) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `applications_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offre_emploi`
--

INSERT INTO `offre_emploi` (`id_offer`, `titre`, `description`, `skills_needed`, `budget`, `duree`, `image_path`, `id_projet`, `id_recruteur`, `status`, `verification_status`, `verified_at`, `verified_by`, `moderation_note`, `created_at`, `location`, `contact_email`, `salary_min`, `salary_max`, `employment_type`, `experience_level`, `expires_at`, `views_count`, `applications_count`) VALUES
(14, 'triza', 'adad', 'Design artisanal moderne, Prompt Engineering', 450.00, '50', 'uploads/offers/offer_69f4c881c7921.jpg', 4, 3, 'active', 'verified', '2026-05-01 17:42:01', 4, NULL, '2026-05-01 16:36:33', 'ariaana', 'emir.naasri@gmail.com', NULL, NULL, NULL, NULL, NULL, 5, 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_users`
--

CREATE TABLE `pending_users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('artisan','recruteur','admin','entrepreneur') NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_users`
--

INSERT INTO `pending_users` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `token`, `expires_at`, `created_at`) VALUES
(2, 'abderagke', 'azeag', 'abdoumili04@gmail.com', '$2y$10$irhI27Or61emLIpKdTcrueduGIZxwUA3hKv0u/ljuRZ1Ee1PCC2NO', 'entrepreneur', 'd98a13152d310c5f7a5ad0d2dd0547722e5ca506a67ab54394d5b2c18b59fc82', '2026-04-12 16:57:20', '2026-04-11 15:57:20'),
(5, 'abderahmen', 'mili', 'abdou123@admin.com', '$2y$10$0GSbvCvdcZXDsDqh7ZokrOw9pXk2ck/Dl3lmcO7fYfZ5aal.6HrtG', 'entrepreneur', '37f5fae3ea9f304635b4b46d13a4487054b8880ff7921c4ebf15c4345a518125', '2026-04-18 12:34:25', '2026-04-17 11:34:25');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_files`
--

CREATE TABLE `portfolio_files` (
  `id_portfolio_file` int(11) NOT NULL,
  `id_user` bigint(20) NOT NULL,
  `titre` varchar(150) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `realisation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portfolio_files`
--

INSERT INTO `portfolio_files` (`id_portfolio_file`, `id_user`, `titre`, `file_name`, `file_path`, `created_at`, `realisation`) VALUES
(2, 5, 'Catalogue services IA 2026', 'catalogue_ia_2026.pdf', '/pyj_copy/public/uploads/portfolio/catalogue_ia_2026.pdf', '2026-04-23 19:11:00', 'Catalogue IA'),
(3, 5, 'Etude cas Atelier Céramique', 'cas_atelier_ceramique.pdf', '/pyj_copy/public/uploads/portfolio/cas_atelier_ceramique.pdf', '2026-04-23 19:11:00', 'Etude de cas'),
(4, 5, 'Guide automatisation CRM', 'guide_crm.pdf', '/pyj_copy/public/uploads/portfolio/guide_crm.pdf', '2026-04-23 19:11:00', 'Guide pratique'),
(5, 4, 'Site e-commerce de vêtements', 'ecommerce_clothes.pdf', '/uploads/portfolio/4/ecommerce_clothes.pdf', '2025-09-12 10:15:00', 'Développement complet front-end + back-end avec panier et paiement'),
(6, 4, 'Application mobile de gestion de tâches', 'task_app_mockup.png', '/uploads/portfolio/4/task_app_mockup.png', '2025-10-02 14:30:00', 'UI/UX design et prototype d’une app mobile de productivité'),
(7, 4, 'Dashboard admin analytics', 'admin_dashboard.zip', '/uploads/portfolio/4/admin_dashboard.zip', '2025-10-18 09:45:00', 'Création d’un dashboard avec statistiques utilisateurs et graphiques'),
(8, 4, 'Portfolio personnel en ligne', 'personal_portfolio.html', '/uploads/portfolio/4/personal_portfolio.html', '2025-11-01 18:20:00', 'Site portfolio responsive avec animations et présentation CV'),
(9, 4, 'API REST gestion utilisateurs', 'api_users_doc.pdf', '/uploads/portfolio/4/api_users_doc.pdf', '2025-11-10 16:05:00', 'Conception et documentation d’une API REST sécurisée en PHP');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_projects`
--

CREATE TABLE `portfolio_projects` (
  `id_portfolio_project` bigint(20) NOT NULL,
  `id_user` bigint(20) NOT NULL,
  `titre` varchar(140) NOT NULL,
  `categorie` varchar(80) NOT NULL,
  `short_description` varchar(280) NOT NULL,
  `detail_description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_project_competence`
--

CREATE TABLE `portfolio_project_competence` (
  `id_portfolio_project` bigint(20) NOT NULL,
  `id_competence` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_project_documents`
--

CREATE TABLE `portfolio_project_documents` (
  `id_portfolio_document` bigint(20) NOT NULL,
  `id_portfolio_project` bigint(20) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profil_competence`
--

CREATE TABLE `profil_competence` (
  `id_profil` bigint(20) NOT NULL,
  `id_competence` bigint(20) NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_competence`
--

INSERT INTO `profil_competence` (`id_profil`, `id_competence`, `date_ajout`) VALUES
(1, 10, '2026-04-25 07:45:05'),
(1, 10, '2026-04-25 07:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `profil_competences`
--

CREATE TABLE `profil_competences` (
  `id_profil` int(11) NOT NULL,
  `id_competences` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profil_professionnel`
--

CREATE TABLE `profil_professionnel` (
  `id_profil` bigint(20) NOT NULL,
  `id_user` bigint(20) DEFAULT NULL,
  `specialite` text DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'Disponible',
  `horaires` varchar(100) DEFAULT NULL,
  `insight_profile_score` tinyint(3) UNSIGNED DEFAULT NULL,
  `insight_popularity_score` tinyint(3) UNSIGNED DEFAULT NULL,
  `insight_suggested_jobs` tinyint(3) UNSIGNED DEFAULT NULL,
  `insight_is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `insight_last_calc_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_professionnel`
--

INSERT INTO `profil_professionnel` (`id_profil`, `id_user`, `specialite`, `date_creation`, `ville`, `telephone`, `statut`, `horaires`, `insight_profile_score`, `insight_popularity_score`, `insight_suggested_jobs`, `insight_is_trending`, `insight_last_calc_at`) VALUES
(0, 4, 'entrepreneur', '2026-04-17', 'algerie', '00000000', 'Indisponible', 'Temporairement indisponible', 44, 74, 6, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profil_profetionnel`
--

CREATE TABLE `profil_profetionnel` (
  `id_profil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `specialité` varchar(150) NOT NULL,
  `bio` text NOT NULL,
  `experience` text NOT NULL,
  `portfolio` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projet`
--

CREATE TABLE `projet` (
  `id` int(11) NOT NULL,
  `titre` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `skills_needed` text DEFAULT NULL,
  `budget_min` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `image_path` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `id_categorie` int(11) DEFAULT NULL,
  `budget_max` decimal(12,2) DEFAULT NULL,
  `id_createur` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projet`
--

INSERT INTO `projet` (`id`, `titre`, `description`, `skills_needed`, `budget_min`, `status`, `image_path`, `date_creation`, `id_categorie`, `budget_max`, `id_createur`) VALUES
(3, 'test', 'dadad', 'Bois sculpté', 150.00, 'active', 'uploads/projects/project_69ea3b6ee80f30.50455603.jpg', '2026-04-23 16:31:58', NULL, NULL, 3),
(4, 'micheal moilano', 'cqcq', 'Bois sculpté', 1000.00, 'active', 'uploads/projects/project_69eb4c1dca5804.35419959.png', '2026-04-24 11:55:25', NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `quizz`
--

CREATE TABLE `quizz` (
  `id_quizz` bigint(20) NOT NULL,
  `domaine` char(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `duree` bigint(20) DEFAULT NULL,
  `logs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizz_formations`
--

CREATE TABLE `quizz_formations` (
  `id_formation` bigint(20) NOT NULL,
  `id_quizz` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `required_mater`
--

CREATE TABLE `required_mater` (
  `id_projet` bigint(20) NOT NULL,
  `id_materiel` bigint(20) NOT NULL,
  `quantite` int(11) DEFAULT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `required_mater`
--

INSERT INTO `required_mater` (`id_projet`, `id_materiel`, `quantite`, `prix_unitaire`) VALUES
(1, 1, NULL, NULL),
(8, 6, 85, 44.00),
(9, 7, 852, 44.99),
(14, 15, 15, 58.00),
(15, 16, 100, 2.00),
(15, 17, 10, 20.00),
(15, 18, 20, 5.00),
(15, 19, 1, 300.00),
(15, 20, 100, 1.00),
(15, 21, 1, 500.00),
(1, 1, NULL, NULL),
(8, 6, 85, 44.00),
(9, 7, 852, 44.99),
(14, 15, 15, 58.00),
(15, 16, 100, 2.00),
(15, 17, 10, 20.00),
(15, 18, 20, 5.00),
(15, 19, 1, 300.00),
(15, 20, 100, 1.00),
(15, 21, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `required_skills`
--

CREATE TABLE `required_skills` (
  `id_projet` bigint(20) NOT NULL,
  `id_skill` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `required_skills`
--

INSERT INTO `required_skills` (`id_projet`, `id_skill`) VALUES
(1, 1),
(8, 6),
(9, 7),
(14, 15),
(15, 16),
(15, 17),
(15, 18),
(15, 19),
(15, 20),
(1, 1),
(8, 6),
(9, 7),
(14, 15),
(15, 16),
(15, 17),
(15, 18),
(15, 19),
(15, 20);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` bigint(20) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `nom`, `level`) VALUES
(1, 'gre', 'Debutant'),
(2, 'fez', 'Avance'),
(3, 'fez', 'Intermediaire'),
(4, 'fez', 'Debutant'),
(5, 'couture', 'Intermediaire'),
(6, 'gzegz', 'Avance'),
(7, 'egrfzed', 'Intermediaire'),
(8, 'jklm¨', 'Intermediaire'),
(9, 'bjkl', 'Intermediaire'),
(10, 'ghjiop', 'Avance'),
(11, 'Tressage traditionnel', 'Avance'),
(12, 'Design artisanal moderne', 'Intermediaire'),
(13, 'Marketing des produits artisanaux', 'Intermediaire'),
(14, 'Gestion de production', 'Debutant'),
(15, 'Vente (marchÚs / en ligne)', 'Intermediaire');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('artisan','recruteur','admin','entrepreneur') NOT NULL DEFAULT 'artisan',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `etat_compte` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `entreprise_name` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `ville` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `date_creation`, `etat_compte`, `entreprise_name`, `avatar`, `ville`) VALUES
(3, 'amir', 'nasri', 'emir.naasri@gmail.com', '$2y$10$x.Ayq5kXo9hxTQx5F12UxOfZu9ydRme1smqD7R5r6CHRo0ft6KMpa', 'entrepreneur', '2026-04-11 00:00:00', 'actif', NULL, NULL, NULL),
(4, 'Super', 'Admin', 'admin@herfa.tn', '$2y$10$Q.4wegbzXkx9b9fGA1ZhXuSniT34ZIVzGzNV4fb0/OidSFtKF11fu', 'admin', '2026-04-17 11:55:06', 'actif', NULL, NULL, NULL),
(5, 'artis', 'rar', 'emir.nasri21@gmail.com', '$2y$10$VCefPgFRGmYrrKTbzJMSBOTXzH.T8zMjpPrA5NGY6gQpU1UZFZ26i', 'artisan', '2026-04-17 00:00:00', 'actif', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workshops`
--

CREATE TABLE `workshops` (
  `id_workshop` bigint(20) NOT NULL,
  `titre` char(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duree` int(11) DEFAULT NULL,
  `date_publication` date DEFAULT NULL,
  `prix` float DEFAULT NULL,
  `certification` char(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workshops_formation`
--

CREATE TABLE `workshops_formation` (
  `id_formation` bigint(20) NOT NULL,
  `id_workshop` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_application_user_offer` (`id_user`,`id_offer`),
  ADD KEY `idx_application_status` (`status`),
  ADD KEY `idx_application_offer` (`id_offer`),
  ADD KEY `idx_application_user` (`id_user`);

--
-- Indexes for table `application_messages`
--
ALTER TABLE `application_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `application_offre`
--
ALTER TABLE `application_offre`
  ADD PRIMARY KEY (`id_application`,`id_offre`),
  ADD KEY `fk_ao_offer` (`id_offre`),
  ADD KEY `fk_ao_application` (`id_application`);

--
-- Indexes for table `artisan_onboarding`
--
ALTER TABLE `artisan_onboarding`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_artisan_onboarding_user` (`user_id`);

--
-- Indexes for table `candidate_education`
--
ALTER TABLE `candidate_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profile` (`id_candidate_profile`);

--
-- Indexes for table `candidate_experience`
--
ALTER TABLE `candidate_experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profile` (`id_candidate_profile`);

--
-- Indexes for table `candidate_profile`
--
ALTER TABLE `candidate_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_application` (`id_application`),
  ADD KEY `idx_application` (`id_application`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `candidate_skills`
--
ALTER TABLE `candidate_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profile` (`id_candidate_profile`),
  ADD KEY `idx_skill` (`skill_name`);

--
-- Indexes for table `certification`
--
ALTER TABLE `certification`
  ADD PRIMARY KEY (`id_certification`),
  ADD KEY `fk_cert_user` (`id_user`);

--
-- Indexes for table `competences`
--
ALTER TABLE `competences`
  ADD PRIMARY KEY (`id_competence`);

--
-- Indexes for table `competence_catalog`
--
ALTER TABLE `competence_catalog`
  ADD PRIMARY KEY (`id_competence_catalog`);

--
-- Indexes for table `experience`
--
ALTER TABLE `experience`
  ADD PRIMARY KEY (`id_experience`),
  ADD KEY `fk_exp_user` (`id_user`);

--
-- Indexes for table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materiel`
--
ALTER TABLE `materiel`
  ADD PRIMARY KEY (`id_materiel`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `offer_bookmarks`
--
ALTER TABLE `offer_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- Indexes for table `offer_reports`
--
ALTER TABLE `offer_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer_id` (`offer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `offer_views`
--
ALTER TABLE `offer_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_offer_views_offer` (`offer_id`);

--
-- Indexes for table `offre_emploi`
--
ALTER TABLE `offre_emploi`
  ADD PRIMARY KEY (`id_offer`),
  ADD KEY `fk_offer_project` (`id_projet`),
  ADD KEY `idx_offer_status_created` (`status`,`created_at`),
  ADD KEY `idx_offer_verification_created` (`verification_status`,`created_at`),
  ADD KEY `idx_offer_verified_by` (`verified_by`),
  ADD KEY `idx_offer_status` (`status`),
  ADD KEY `idx_offer_verification` (`verification_status`),
  ADD KEY `idx_offer_recruiter` (`id_recruteur`),
  ADD KEY `idx_offer_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `pending_users`
--
ALTER TABLE `pending_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `portfolio_files`
--
ALTER TABLE `portfolio_files`
  ADD PRIMARY KEY (`id_portfolio_file`),
  ADD KEY `fk_portfolio_user` (`id_user`);

--
-- Indexes for table `profil_competences`
--
ALTER TABLE `profil_competences`
  ADD PRIMARY KEY (`id_profil`,`id_competences`),
  ADD KEY `fk_pc_comp` (`id_competences`);

--
-- Indexes for table `profil_profetionnel`
--
ALTER TABLE `profil_profetionnel`
  ADD PRIMARY KEY (`id_profil`),
  ADD KEY `fk_profil_user` (`id_user`);

--
-- Indexes for table `projet`
--
ALTER TABLE `projet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_projet_createur` (`id_createur`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `application_messages`
--
ALTER TABLE `application_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate_education`
--
ALTER TABLE `candidate_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate_experience`
--
ALTER TABLE `candidate_experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate_profile`
--
ALTER TABLE `candidate_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `candidate_skills`
--
ALTER TABLE `candidate_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competences`
--
ALTER TABLE `competences`
  MODIFY `id_competence` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `competence_catalog`
--
ALTER TABLE `competence_catalog`
  MODIFY `id_competence_catalog` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `offer_bookmarks`
--
ALTER TABLE `offer_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offer_reports`
--
ALTER TABLE `offer_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offer_views`
--
ALTER TABLE `offer_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offre_emploi`
--
ALTER TABLE `offre_emploi`
  MODIFY `id_offer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pending_users`
--
ALTER TABLE `pending_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `profil_profetionnel`
--
ALTER TABLE `profil_profetionnel`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projet`
--
ALTER TABLE `projet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `fk_application_offer` FOREIGN KEY (`id_offer`) REFERENCES `offre_emploi` (`id_offer`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `application_messages`
--
ALTER TABLE `application_messages`
  ADD CONSTRAINT `application_messages_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `application_offre`
--
ALTER TABLE `application_offre`
  ADD CONSTRAINT `fk_ao_application` FOREIGN KEY (`id_application`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ao_offer` FOREIGN KEY (`id_offre`) REFERENCES `offre_emploi` (`id_offer`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_education`
--
ALTER TABLE `candidate_education`
  ADD CONSTRAINT `fk_candidate_education_profile` FOREIGN KEY (`id_candidate_profile`) REFERENCES `candidate_profile` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_experience`
--
ALTER TABLE `candidate_experience`
  ADD CONSTRAINT `fk_candidate_experience_profile` FOREIGN KEY (`id_candidate_profile`) REFERENCES `candidate_profile` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_profile`
--
ALTER TABLE `candidate_profile`
  ADD CONSTRAINT `fk_candidate_profile_app` FOREIGN KEY (`id_application`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_candidate_profile_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `candidate_skills`
--
ALTER TABLE `candidate_skills`
  ADD CONSTRAINT `fk_candidate_skills_profile` FOREIGN KEY (`id_candidate_profile`) REFERENCES `candidate_profile` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `offer_bookmarks`
--
ALTER TABLE `offer_bookmarks`
  ADD CONSTRAINT `offer_bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `offer_bookmarks_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `offre_emploi` (`id_offer`) ON DELETE CASCADE;

--
-- Constraints for table `offer_reports`
--
ALTER TABLE `offer_reports`
  ADD CONSTRAINT `offer_reports_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offre_emploi` (`id_offer`) ON DELETE CASCADE,
  ADD CONSTRAINT `offer_reports_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `offer_views`
--
ALTER TABLE `offer_views`
  ADD CONSTRAINT `offer_views_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offre_emploi` (`id_offer`) ON DELETE CASCADE,
  ADD CONSTRAINT `offer_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `offre_emploi`
--
ALTER TABLE `offre_emploi`
  ADD CONSTRAINT `fk_offer_project` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_recruiter` FOREIGN KEY (`id_recruteur`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `profil_competences`
--
ALTER TABLE `profil_competences`
  ADD CONSTRAINT `fk_pc_comp` FOREIGN KEY (`id_competences`) REFERENCES `competences` (`id_competence`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_profil` FOREIGN KEY (`id_profil`) REFERENCES `profil_profetionnel` (`id_profil`) ON DELETE CASCADE;

--
-- Constraints for table `profil_profetionnel`
--
ALTER TABLE `profil_profetionnel`
  ADD CONSTRAINT `fk_profil_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `projet`
--
ALTER TABLE `projet`
  ADD CONSTRAINT `fk_projet_createur` FOREIGN KEY (`id_createur`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
