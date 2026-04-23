-- Herfa Tunisie job platform upgrade migration
-- Adds CV storage, structured candidate data, and anti-duplicate application rules.

START TRANSACTION;

ALTER TABLE `offre_emploi`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  ADD COLUMN IF NOT EXISTS `verification_status` ENUM('not_verified','verified') NOT NULL DEFAULT 'not_verified',
  ADD COLUMN IF NOT EXISTS `views_count` INT(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `applications_count` INT(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `expires_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `salary_min` DECIMAL(10,2) NULL,
  ADD COLUMN IF NOT EXISTS `salary_max` DECIMAL(10,2) NULL,
  ADD COLUMN IF NOT EXISTS `employment_type` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `experience_level` VARCHAR(50) NULL,
  ADD INDEX IF NOT EXISTS `idx_offer_status` (`status`),
  ADD INDEX IF NOT EXISTS `idx_offer_verification` (`verification_status`),
  ADD INDEX IF NOT EXISTS `idx_offer_recruiter` (`id_recruteur`),
  ADD INDEX IF NOT EXISTS `idx_offer_created_at` (`created_at`);

ALTER TABLE `application`
  ADD COLUMN IF NOT EXISTS `parsed_cv_data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(`parsed_cv_data`)),
  ADD COLUMN IF NOT EXISTS `cv_parsing_status` ENUM('pending','parsing','success','failed','manual_entry') DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `cv_parsed_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `cv_file_name` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `cv_file_size` INT(11) NULL,
  ADD COLUMN IF NOT EXISTS `cv_file_type` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `cv_file_hash` VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  ADD UNIQUE KEY IF NOT EXISTS `uq_application_user_offer` (`id_user`, `id_offer`),
  ADD INDEX IF NOT EXISTS `idx_application_status` (`status`),
  ADD INDEX IF NOT EXISTS `idx_application_user` (`id_user`),
  ADD INDEX IF NOT EXISTS `idx_application_offer` (`id_offer`);

CREATE TABLE IF NOT EXISTS `candidate_profile` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_application` INT(11) NOT NULL,
  `id_user` INT(11) DEFAULT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `professional_title` VARCHAR(255) DEFAULT NULL,
  `professional_summary` TEXT DEFAULT NULL,
  `skills` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `education` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education`)),
  `work_experience` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_experience`)),
  `certifications` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certifications`)),
  `languages` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `profile_completeness` INT(11) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_candidate_profile_application` (`id_application`),
  KEY `idx_candidate_profile_user` (`id_user`),
  KEY `idx_candidate_profile_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `candidate_skills` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_candidate_profile` INT(11) NOT NULL,
  `skill_name` VARCHAR(100) DEFAULT NULL,
  `proficiency_level` ENUM('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `years_of_experience` DECIMAL(3,1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_candidate_skills_profile` (`id_candidate_profile`),
  KEY `idx_candidate_skills_name` (`skill_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `candidate_education` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_candidate_profile` INT(11) NOT NULL,
  `school_name` VARCHAR(255) DEFAULT NULL,
  `degree` VARCHAR(100) DEFAULT NULL,
  `field_of_study` VARCHAR(255) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_candidate_education_profile` (`id_candidate_profile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `candidate_experience` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_candidate_profile` INT(11) NOT NULL,
  `company_name` VARCHAR(255) DEFAULT NULL,
  `job_title` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_current` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_candidate_experience_profile` (`id_candidate_profile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
