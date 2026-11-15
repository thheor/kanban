-- Kanban schema, Trello-style: boards contain user-defined lists, lists contain cards,
-- cards carry members, labels, checklist items and attachments.
-- Portable between MariaDB 10.4 (Windows XAMPP) and mariadb:10.4 (Docker).
--
-- Import: mysql -h127.0.0.1 -P3307 -ukanban -pkanban < sql/schema.sql
--      or: phpMyAdmin -> Import -> sql/schema.sql

CREATE DATABASE IF NOT EXISTS `kanban`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kanban`;

-- Scaffold stage only: this file rebuilds from scratch. Once the app holds real data,
-- drop this block and switch to numbered migration files instead.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `attachments`, `task_labels`, `task_members`, `task_items`, `labels`,
  `tasks`, `lists`, `boards`, `board_members`, `remember_tokens`, `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_path` VARCHAR(255) DEFAULT NULL,
  `pronouns` VARCHAR(30) DEFAULT NULL,
  `job_title` VARCHAR(80) DEFAULT NULL,
  `department` VARCHAR(80) DEFAULT NULL,
  `about` TEXT DEFAULT NULL,
  `role` ENUM('owner','member') NOT NULL DEFAULT 'member',
  `failed_logins` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `remember_tokens` (
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

CREATE TABLE `boards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `assignee_id` INT UNSIGNED DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `boards_user_id_index` (`user_id`),
  KEY `boards_assignee_id_index` (`assignee_id`),
  CONSTRAINT `boards_user_id_foreign` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boards_assignee_id_foreign` FOREIGN KEY (`assignee_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_members` (
  `board_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `role` ENUM('admin','member') NOT NULL DEFAULT 'member',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`board_id`, `user_id`),
  KEY `board_members_user_id_index` (`user_id`),
  CONSTRAINT `board_members_board_id_foreign` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `board_members_user_id_foreign` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `is_done` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lists_board_position_index` (`board_id`, `position`),
  CONSTRAINT `lists_board_id_foreign` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id` INT UNSIGNED NOT NULL,
  `list_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `completed_at` DATETIME DEFAULT NULL,
  `archived_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tasks_board_id_index` (`board_id`),
  KEY `tasks_list_position_index` (`list_id`, `position`),
  KEY `tasks_due_date_index` (`due_date`),
  KEY `tasks_completed_at_index` (`completed_at`),
  CONSTRAINT `tasks_board_id_foreign` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE,
  -- RESTRICT: a list holding cards cannot vanish underneath them; archive the list instead.
  CONSTRAINT `tasks_list_id_foreign` FOREIGN KEY (`list_id`)
    REFERENCES `lists` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_members` (
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`task_id`, `user_id`),
  KEY `task_members_user_id_index` (`user_id`),
  CONSTRAINT `task_members_task_id_foreign` FOREIGN KEY (`task_id`)
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_members_user_id_foreign` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `labels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `board_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(40) DEFAULT NULL,
  `color` ENUM('green','yellow','orange','red','purple','blue','sky','lime','pink','grey')
    NOT NULL DEFAULT 'grey',
  `position` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `labels_board_id_index` (`board_id`),
  CONSTRAINT `labels_board_id_foreign` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_labels` (
  `task_id` INT UNSIGNED NOT NULL,
  `label_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`task_id`, `label_id`),
  KEY `task_labels_label_id_index` (`label_id`),
  CONSTRAINT `task_labels_task_id_foreign` FOREIGN KEY (`task_id`)
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_labels_label_id_foreign` FOREIGN KEY (`label_id`)
    REFERENCES `labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_items` (
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

CREATE TABLE `attachments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) NOT NULL,
  `size_bytes` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `attachments_task_id_index` (`task_id`),
  CONSTRAINT `attachments_task_id_foreign` FOREIGN KEY (`task_id`)
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachments_user_id_foreign` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
