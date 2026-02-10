-- Migration: Add admin system and seating requests
-- Run these SQL queries in phpMyAdmin or MySQL console

-- 1. Alter teachers table to add role and status columns
ALTER TABLE teachers ADD COLUMN role VARCHAR(20) DEFAULT 'teacher' AFTER username;
ALTER TABLE teachers ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER role;

-- 2. Alter classes table to add adviser_id
ALTER TABLE classes ADD COLUMN adviser_id INT DEFAULT NULL AFTER teacher_id;

-- 3. Create seat_requests table
CREATE TABLE IF NOT EXISTS seat_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requesting_teacher_id INT NOT NULL,
    adviser_teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requesting_teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (adviser_teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- 4. Create seating_plans table
CREATE TABLE IF NOT EXISTS seating_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seat_request_id INT NOT NULL,
    adviser_teacher_id INT NOT NULL,
    seating_data LONGTEXT,
    approved INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seat_request_id) REFERENCES seat_requests(id),
    FOREIGN KEY (adviser_teacher_id) REFERENCES teachers(id)
);

-- Note: Run these queries to update existing data (optional):
-- UPDATE teachers SET status = 'active' WHERE id IS NOT NULL;
-- UPDATE teachers SET role = 'admin' WHERE id = 1; -- Set first user as admin
