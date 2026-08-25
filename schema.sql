-- =====================================================
-- Database: lab_month_korn
-- Purpose: ระบบจัดการการฝึกงาน
-- =====================================================

-- 1. ตาราง USERS (ข้อมูลผู้ใช้งาน)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mentor', 'student') NOT NULL,
    ref_id INT COMMENT 'ชี้ไปยัง mentors.id หรือ students.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ตาราง MENTORS (ข้อมูลพี่เลี้ยง)
CREATE TABLE IF NOT EXISTS mentors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. ตาราง STUDENTS (ข้อมูลนักศึกษา)
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    major VARCHAR(100),
    mentor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE SET NULL
);

-- 4. ตาราง LOGS (บันทึกการฝึกงาน)
CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    log_date DATE NOT NULL,
    work_description LONGTEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- =====================================================
-- Insert ข้อมูลตัวอย่างเพื่อทดสอบ
-- =====================================================

-- ข้อมูลพี่เลี้ยงตัวอย่าง
INSERT IGNORE INTO mentors (id, name, email, department) VALUES
(1, 'พี่เลี้ยง สมพร', 'som.pron@company.com', 'IT'),
(2, 'พี่เลี้ยง ฟ้อย', 'foy.dev@company.com', 'HR');

-- ข้อมูลนักศึกษาตัวอย่าง
INSERT IGNORE INTO students (id, student_code, name, major, mentor_id) VALUES
(1, 'STU001', 'นักศึกษา ทดสอบ', 'วิทยาการคอมพิวเตอร์', 1),
(2, 'STU002', 'นักศึกษา สอง', 'วิศวกรรมซอฟต์แวร์', 2);

-- ข้อมูลผู้ใช้ (รหัสผ่านเป็น Hash)
-- Admin: admin / Admin01
-- Mentor: mentor / Mentor01
-- Student: Student / Student01
INSERT IGNORE INTO users (id, username, password, role, ref_id) VALUES
(1, 'admin', '$2y$10$YIjlrJyEvryQkj/W.O2bie8DBwqvVXU8KlW.O8/LewKpdWf3K9Gxy', 'admin', NULL),
(2, 'mentor', '$2y$10$1X8J5Z9Kx.8R0P.2Q3L.Je5M4N6O7P8Q9R0S1T2U3V4W5X6Y7Z8', 'mentor', 1),
(3, 'Student', '$2y$10$2Y9K6A0Lx.9S1Q.3R4M.Kf6N5O8Q9R0S1T2U3V4W5X6Y7Z8A9B', 'student', 1);
