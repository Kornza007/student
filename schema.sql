-- =====================================================
-- Database: lab_month_korn
-- Purpose: ระบบจัดการและบันทึกการฝึกงานของนักศึกษา (สมบูรณ์)
-- =====================================================

-- 1. ตาราง USERS (ข้อมูลผู้ใช้งานและสิทธิ์)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mentor', 'student') NOT NULL,
    ref_id INT DEFAULT NULL COMMENT 'ชี้ไปยัง mentors.id หรือ students.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตาราง MENTORS (ข้อมูลพี่เลี้ยง)
CREATE TABLE IF NOT EXISTS mentors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว',
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตาราง STUDENTS (ข้อมูลนักศึกษา)
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    student_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    major VARCHAR(100),
    university VARCHAR(150) DEFAULT NULL,
    faculty VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    duration_days INT DEFAULT 90,
    mentor_id INT DEFAULT NULL,
    internship_status ENUM('active','completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว',
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตาราง INTERNSHIP_LOGS (บันทึกการฝึกงานรายวัน)
CREATE TABLE IF NOT EXISTS internship_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    log_date DATE NOT NULL,
    work_description LONGTEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'revision') DEFAULT 'pending',
    mentor_comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว',
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตาราง EVALUATIONS (การประเมินผลการฝึกงาน)
CREATE TABLE IF NOT EXISTS evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    mentor_id INT DEFAULT NULL,
    score_work INT DEFAULT 0,
    score_time INT DEFAULT 0,
    score_behavior INT DEFAULT 0,
    final_feedback TEXT DEFAULT NULL,
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว',
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ตาราง AUDIT_LOGS (บันทึกประวัติการใช้งานทุกบทบาทและกิจกรรม)
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT COMMENT 'ID ผู้ดำเนินการ (users.id)',
    username VARCHAR(100) COMMENT 'ชื่อผู้ใช้',
    user_role VARCHAR(20) COMMENT 'Role (admin/mentor/student)',
    action_type ENUM('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','RESTORE','PERMANENT_DELETE') NOT NULL,
    table_name VARCHAR(100) COMMENT 'ตารางที่ถูกกระทำ',
    record_id INT COMMENT 'ID ของแถวข้อมูล',
    old_values JSON DEFAULT NULL COMMENT 'ค่าก่อนเปลี่ยน',
    new_values JSON DEFAULT NULL COMMENT 'ค่าหลังเปลี่ยน',
    description TEXT COMMENT 'คำอธิบายการกระทำ',
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action_type),
    INDEX idx_table (table_name),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
