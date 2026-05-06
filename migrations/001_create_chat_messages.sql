-- Migration: create chat_messages table
-- Run this SQL in your MySQL database (e.g., via phpMyAdmin or mysql CLI)

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profil_id` BIGINT NOT NULL DEFAULT 0,
  `user_id` BIGINT NOT NULL DEFAULT 0,
  `role` VARCHAR(16) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_profil` (`profil_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
