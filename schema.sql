-- Create database if it does not exist (fallback)
--CREATE DATABASE IF NOT EXISTS `smart_learning_hub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--USE `smart_learning_hub`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) DEFAULT 'student',
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_token_expires` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Courses Table
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `difficulty` VARCHAR(50) DEFAULT 'Beginner', -- 'Beginner', 'Intermediate', 'Advanced'
  `duration` VARCHAR(50) DEFAULT '2 hours',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Lessons Table
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Lesson Progress Table
CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `user_id` INT NOT NULL,
  `lesson_id` INT NOT NULL,
  `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `lesson_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Flashcard Decks Table
CREATE TABLE IF NOT EXISTS `flashcard_decks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL, -- NULL means global system deck
  `title` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Flashcards Table
CREATE TABLE IF NOT EXISTS `flashcards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `deck_id` INT NOT NULL,
  `front` TEXT NOT NULL,
  `back` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`deck_id`) REFERENCES `flashcard_decks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Quizzes Table
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `duration_mins` INT DEFAULT 15,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Questions Table
CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_option` CHAR(1) NOT NULL, -- 'A', 'B', 'C', or 'D'
  `explanation` TEXT DEFAULT NULL,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Quiz Attempts Table
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `quiz_id` INT NOT NULL,
  `score` INT NOT NULL, -- percentage score (0 to 100)
  `total_questions` INT NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Notes Table
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT,
  `category` VARCHAR(50) DEFAULT 'General',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Study Sessions Table (Dashboard logs)
CREATE TABLE IF NOT EXISTS `study_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `duration_seconds` INT DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_date_unique` (`user_id`, `session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MIGRATION: Run these if upgrading an existing installation
-- ============================================================
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `gender` VARCHAR(20) DEFAULT NULL AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `country` VARCHAR(100) DEFAULT NULL AFTER `gender`;
