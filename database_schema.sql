-- Database Schema for Role-Based Authentication
-- CyberTirah Framework

-- Create user_info table if not exists
CREATE TABLE IF NOT EXISTS `user_info` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'teacher', 'admin', 'parent', 'staff') NOT NULL DEFAULT 'student',
  `phone_number` VARCHAR(20) NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email_role` (`email`, `role`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create roles table for dynamic role management
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  `role_slug` VARCHAR(50) NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NULL DEFAULT '👤',
  `color` VARCHAR(20) NULL DEFAULT '#667eea',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `unique_role_slug` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`role_name`, `role_slug`, `display_name`, `icon`, `color`, `is_active`) VALUES
('Student', 'student', 'Student Login', '👨‍🎓', '#667eea', 1),
('Teacher', 'teacher', 'Teacher Login', '👨‍🏫', '#764ba2', 1),
('Admin', 'admin', 'Admin Login', '👨‍💼', '#dc3545', 1),
('Parent', 'parent', 'Parent Login', '👪', '#28a745', 1),
('Staff', 'staff', 'Staff Login', '👔', '#ffc107', 1)
ON DUPLICATE KEY UPDATE 
  `display_name` = VALUES(`display_name`),
  `icon` = VALUES(`icon`),
  `color` = VALUES(`color`);

-- Insert sample admin user (password: admin123)
INSERT INTO `user_info` (`first_name`, `last_name`, `username`, `email`, `password`, `role`, `status`) VALUES
('Admin', 'User', 'admin', 'admin@frame.ct.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Insert sample users for testing
INSERT INTO `user_info` (`first_name`, `last_name`, `username`, `email`, `password`, `role`, `status`) VALUES
('John', 'Student', 'john.student', 'john@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('Jane', 'Teacher', 'jane.teacher', 'jane@teacher.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
('Bob', 'Parent', 'bob.parent', 'bob@parent.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active'),
('Alice', 'Staff', 'alice.staff', 'alice@staff.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Note: All sample passwords are: admin123

-- Create pages table for page management
CREATE TABLE IF NOT EXISTS `pages` (
  `page_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `meta_description` TEXT NULL,
  `meta_keywords` TEXT NULL,
  `status` ENUM('published', 'draft') NOT NULL DEFAULT 'draft',
  `author_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_author` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create html_blocks table for reusable content blocks
CREATE TABLE IF NOT EXISTS `html_blocks` (
  `block_id` INT(11) NOT NULL AUTO_INCREMENT,
  `block_name` VARCHAR(100) NOT NULL,
  `block_slug` VARCHAR(100) NOT NULL,
  `content` TEXT NULL,
  `location` VARCHAR(50) NULL DEFAULT 'global',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_id`),
  UNIQUE KEY `unique_block_slug` (`block_slug`),
  KEY `idx_status` (`status`),
  KEY `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

