-- Teacher's Monitoring System (TMS) Full Database Migration
-- Target Database: tc_monitoring
-- Generated for: MySQL/MariaDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. DATABASE INITIALIZATION
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `tc_monitoring` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `tc_monitoring`;

-- --------------------------------------------------------
-- 2. TEACHERS TABLE
-- Handles Admin, Teachers, and Approval Status
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `fullName` VARCHAR(100) NOT NULL,
  `username` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher') DEFAULT 'teacher',
  `status` ENUM('active', 'pending') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- 3. CLASSES TABLE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(11) NOT NULL,
  `className` VARCHAR(50) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `academic_year` YEAR NOT NULL,
  `is_advisory` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_class_teacher` FOREIGN KEY (`teacher_id`) 
    REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- 4. STUDENTS TABLE
-- Includes Seating Coordinates (Row x Column)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_id` INT(11) NOT NULL,
  `student_name` VARCHAR(100) NOT NULL,
  `seat_row` INT(11) NOT NULL,
  `seat_column` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_student_class` FOREIGN KEY (`class_id`) 
    REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- 5. SCORES TABLE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `scores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `class_id` INT(11) NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `score` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_score_student` FOREIGN KEY (`student_id`) 
    REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_score_class` FOREIGN KEY (`class_id`) 
    REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- 6. SEAT REQUESTS TABLE
-- Workflow for Teachers to request data from Advisers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seat_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `requesting_teacher_id` INT(11) NOT NULL,
  `adviser_teacher_id` INT(11) NOT NULL,
  `class_id` INT(11) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_req_sender` FOREIGN KEY (`requesting_teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `fk_req_receiver` FOREIGN KEY (`adviser_teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `fk_req_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- 7. SEATING PLANS TABLE
-- JSON storage for specific room layouts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seating_plans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `seat_request_id` INT(11) NOT NULL,
  `seating_data` LONGTEXT NOT NULL,
  `approved` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_plan_request` FOREIGN KEY (`seat_request_id`) 
    REFERENCES `seat_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;
