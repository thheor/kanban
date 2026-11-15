-- Kanban schema. Portable between MariaDB 10.4 (Windows XAMPP) and mariadb:10.4 (Docker).
-- Import: mysql -h127.0.0.1 -P3307 -ukanban -pkanban < sql/schema.sql
--      or: phpMyAdmin -> Import -> sql/schema.sql

CREATE DATABASE IF NOT EXISTS `kanban`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kanban`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_path` VARCHAR(255) DEFAULT NULL,
  `theme` ENUM('light','dark','system') NOT NULL DEFAULT 'system',
  `failed_logins` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(24) NOT NULL,
  `validator_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remember_tokens_selector_unique` (`selector`),
  KEY `remember_tokens_user_id_index` (`user_id`),
  CONSTRAINT `remember_tokens_user_id_foreign` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `boards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `boards_user_id_index` (`user_id`),
  CONSTRAINT `boards_user_id_foreign` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('not_started','in_progress','blocked','done') NOT NULL DEFAULT 'not_started',
  `priority` ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
  `due_date` DATE DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tasks_board_status_position_index` (`board_id`, `status`, `position`),
  KEY `tasks_due_date_index` (`due_date`),
  CONSTRAINT `tasks_board_id_foreign` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `task_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `is_done` TINYINT(1) NOT NULL DEFAULT 0,
  `position` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `task_items_task_position_index` (`task_id`, `position`),
  CONSTRAINT `task_items_task_id_foreign` FOREIGN KEY (`task_id`)
    REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
