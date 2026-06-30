-- Palghar LIVE MySQL Database Schema Setup (Phase 1)
-- Only contains core content tables: sections, section_images, news, comments, inquiries

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `section_images`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Sections Table
CREATE TABLE IF NOT EXISTS `sections` (
    `id` VARCHAR(50) PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Section Images Table
CREATE TABLE IF NOT EXISTS `section_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_id` VARCHAR(50) NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. News Table
CREATE TABLE IF NOT EXISTS `news` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `summary` VARCHAR(500) NOT NULL,
    `content` TEXT NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `author` VARCHAR(100) NOT NULL,
    `date_published` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `views` INT DEFAULT 0,
    `trending` TINYINT(1) DEFAULT 0,
    `featured` TINYINT(1) DEFAULT 0,
    `language` VARCHAR(10) DEFAULT 'en',
    FOREIGN KEY (`category`) REFERENCES `sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Comments Table
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `news_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `text` TEXT NOT NULL,
    `date_posted` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Inquiries Table
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'contact',
    `date_received` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'editor',
    `status` VARCHAR(20) NOT NULL DEFAULT 'enabled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Permissions Table
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `section_id` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
