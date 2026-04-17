-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 02:10 PM
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
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `competences`
--

CREATE TABLE `competences` (
  `id_competences` int(11) NOT NULL,
  `competence` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `nombre_projets` int(11) NOT NULL DEFAULT 0,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competences`
--

INSERT INTO `competences` (`id_competences`, `competence`, `description`, `nombre_projets`, `date_ajout`) VALUES
(1, 'Poterie', 'Fabrication d’objets en céramique', 12, '2026-04-11 16:03:26'),
(2, 'Broderie', 'Broderie traditionnelle tunisienne', 8, '2026-04-11 16:03:26'),
(3, 'Bois sculpté', 'Travail artisanal du bois', 5, '2026-04-11 16:03:26');

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
(7, 'carnava', 'dadadadada', 'Bois sculpté', 50.00, '50', 'uploads/offers/offer_69e214684ffa5.png', 2, 3, 'active', 'not_verified', NULL, NULL, NULL, '2026-04-17 12:07:20', 'ariana', 'emir.naasri@gmail.com', NULL, NULL, NULL, NULL, NULL, 0, 0);

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
-- Table structure for table `profil_competences`
--

CREATE TABLE `profil_competences` (
  `id_profil` int(11) NOT NULL,
  `id_competences` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Collection céramique 2026', '', NULL, 2000.00, 'open', NULL, '2026-04-11 16:03:26', 1, NULL, NULL),
(2, 'Atelier broderie premium', '', NULL, 1500.00, 'open', NULL, '2026-04-11 16:03:26', 2, NULL, NULL);

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_application_user` (`id_user`),
  ADD KEY `idx_application_status` (`status`),
  ADD KEY `idx_application_offer` (`id_offer`);

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
-- Indexes for table `competences`
--
ALTER TABLE `competences`
  ADD PRIMARY KEY (`id_competences`);

--
-- Indexes for table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `fk_offer_recruiter` (`id_recruteur`),
  ADD KEY `idx_offer_status_created` (`status`,`created_at`),
  ADD KEY `idx_offer_verification_created` (`verification_status`,`created_at`),
  ADD KEY `idx_offer_verified_by` (`verified_by`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_messages`
--
ALTER TABLE `application_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competences`
--
ALTER TABLE `competences`
  MODIFY `id_competences` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_offer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pending_users`
--
ALTER TABLE `pending_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `profil_profetionnel`
--
ALTER TABLE `profil_profetionnel`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projet`
--
ALTER TABLE `projet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `fk_pc_comp` FOREIGN KEY (`id_competences`) REFERENCES `competences` (`id_competences`) ON DELETE CASCADE,
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
