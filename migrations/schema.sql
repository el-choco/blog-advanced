-- Blog Advanced Database Schema v2.0
-- Generated: 2025-01-20
-- Author: el-choco

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- USERS TABLE (Multi-User Support)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'editor', 'viewer') DEFAULT 'viewer',
    `avatar` VARCHAR(255),
    `display_name` VARCHAR(100),
    `bio` TEXT,
    `two_factor_secret` VARCHAR(32),
    `two_factor_enabled` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME,
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COMMENTS TABLE (Comment System)
-- =====================================================
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `post_id` VARCHAR(50) NOT NULL,
    `user_id` INT,
    `parent_id` INT NULL,
    `author_name` VARCHAR(100),
    `author_email` VARCHAR(100),
    `author_ip` VARCHAR(45),
    `text` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'spam', 'deleted') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BACKUPS TABLE (Backup Management)
-- =====================================================
CREATE TABLE IF NOT EXISTS `backups` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(500),
    `type` ENUM('manual', 'automatic', 'scheduled') DEFAULT 'manual',
    `size` BIGINT,
    `includes_files` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    `status` ENUM('success', 'failed', 'in_progress') DEFAULT 'success',
    `error_message` TEXT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOG TABLE (Activity Tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50),
    `entity_id` VARCHAR(50),
    `old_value` TEXT,
    `new_value` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOGIN ATTEMPTS TABLE (Security)
-- Blog Advanced Database Schema v2.0
-- Generated: 2025-01-20
-- Author: el-choco

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- USERS TABLE (Multi-User Support)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'editor', 'viewer') DEFAULT 'viewer',
    `avatar` VARCHAR(255),
    `display_name` VARCHAR(100),
    `bio` TEXT,
    `two_factor_secret` VARCHAR(32),
    `two_factor_enabled` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME,
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COMMENTS TABLE (Comment System)
-- =====================================================
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `post_id` VARCHAR(50) NOT NULL,
    `user_id` INT,
    `parent_id` INT NULL,
    `author_name` VARCHAR(100),
    `author_email` VARCHAR(100),
    `author_ip` VARCHAR(45),
    `text` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'spam', 'deleted') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BACKUPS TABLE (Backup Management)
-- =====================================================
CREATE TABLE IF NOT EXISTS `backups` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(500),
    `type` ENUM('manual', 'automatic', 'scheduled') DEFAULT 'manual',
    `size` BIGINT,
    `includes_files` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    `status` ENUM('success', 'failed', 'in_progress') DEFAULT 'success',
    `error_message` TEXT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOG TABLE (Activity Tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50),
    `entity_id` VARCHAR(50),
    `old_value` TEXT,
    `new_value` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOGIN ATTEMPTS TABLE (Security)
-- =====================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50),
    `ip_address` VARCHAR(45) NOT NULL,
    `success` BOOLEAN DEFAULT FALSE,
    `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_username` (`username`),
    INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- IP WHITELIST TABLE (Security)
-- =====================================================
CREATE TABLE IF NOT EXISTS `ip_whitelist` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `ip_address` VARCHAR(45) UNIQUE NOT NULL,
    `description` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SESSIONS TABLE (Session Management)
-- =====================================================
CREATE TABLE IF NOT EXISTS `sessions` (
    `session_id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `last_activity` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS TABLE (Notification System)
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT,
    `link` VARCHAR(500),
    `read_at` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_read` (`read_at`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SCHEDULED TASKS TABLE (Automation)
-- =====================================================
CREATE TABLE IF NOT EXISTS `scheduled_tasks` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_type` VARCHAR(50) NOT NULL,
    `frequency` ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL,
    `next_run` DATETIME NOT NULL,
    `last_run` DATETIME,
    `status` ENUM('active', 'paused', 'failed') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_next_run` (`next_run`),
    INDEX `idx_task_type` (`task_type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Username: admin
-- Password: admin123 (CHANGE THIS IMMEDIATELY!)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `display_name`, `status`) 
VALUES (
    'admin',
-- =====================================================
-- Version: 2.0.0
-- Last Updated: 2025-01-20
-- Description: Complete database schema for Blog Advanced v2.0
-- =====================================================
-- SCHEMA VERSION
ON DUPLICATE KEY UPDATE `task_type` = `task_type`;

COMMIT;
    'admin@example.com',
('cleanup_trash', 'weekly', DATE_ADD(NOW(), INTERVAL 1 WEEK)),
('optimize_images', 'daily', DATE_ADD(NOW(), INTERVAL 1 DAY))
-- Insert default scheduled tasks
INSERT INTO `scheduled_tasks` (`task_type`, `frequency`, `next_run`) VALUES
('backup', 'daily', DATE_ADD(NOW(), INTERVAL 1 DAY)),
    'active'
) ON DUPLICATE KEY UPDATE `username` = 'admin';

    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'super_admin',
    'Administrator',

