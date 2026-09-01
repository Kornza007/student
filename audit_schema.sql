-- =====================================================
-- Audit Log & Soft Delete Schema
-- สำหรับระบบจัดการนักศึกษาฝึกงาน (lab_month_korn)
-- =====================================================

-- 1. ตาราง AUDIT_LOGS (บันทึกประวัติการใช้งานทั้งหมด)
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id       INT PRIMARY KEY AUTO_INCREMENT,
    user_id      INT             COMMENT 'ID ผู้ดำเนินการ (FK → users.id)',
    username     VARCHAR(100)    COMMENT 'ชื่อผู้ใช้ (เก็บไว้เผื่อ user ถูกลบ)',
    user_role    VARCHAR(20)     COMMENT 'Role ของผู้ดำเนินการ',
    action_type  ENUM('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','RESTORE','PERMANENT_DELETE') NOT NULL COMMENT 'ประเภทการกระทำ',
    table_name   VARCHAR(100)    COMMENT 'ตารางที่ถูกกระทำ',
    record_id    INT             COMMENT 'ID ของ record ที่ถูกกระทำ',
    old_values   JSON            COMMENT 'ค่าก่อนเปลี่ยน (ไม่เก็บ password)',
    new_values   JSON            COMMENT 'ค่าหลังเปลี่ยน (ไม่เก็บ password)',
    description  TEXT            COMMENT 'คำอธิบายการกระทำ',
    ip_address   VARCHAR(45)     COMMENT 'IP Address ของผู้ใช้',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'เวลาดำเนินการ',

    INDEX idx_user_id    (user_id),
    INDEX idx_action     (action_type),
    INDEX idx_table      (table_name),
    INDEX idx_record     (table_name, record_id),
    INDEX idx_created    (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 2. เพิ่มคอลัมน์ Soft Delete ให้ตารางหลัก
-- ใช้ IF NOT EXISTS pattern เพื่อรันซ้ำได้ปลอดภัย
-- =====================================================

-- Students
ALTER TABLE students ADD COLUMN IF NOT EXISTS is_deleted  TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว';
ALTER TABLE students ADD COLUMN IF NOT EXISTS deleted_at  DATETIME DEFAULT NULL COMMENT 'เวลาที่ลบ';
ALTER TABLE students ADD COLUMN IF NOT EXISTS deleted_by  INT DEFAULT NULL COMMENT 'ผู้ที่ทำการลบ (user_id)';

-- Mentors
ALTER TABLE mentors ADD COLUMN IF NOT EXISTS is_deleted  TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว';
ALTER TABLE mentors ADD COLUMN IF NOT EXISTS deleted_at  DATETIME DEFAULT NULL COMMENT 'เวลาที่ลบ';
ALTER TABLE mentors ADD COLUMN IF NOT EXISTS deleted_by  INT DEFAULT NULL COMMENT 'ผู้ที่ทำการลบ (user_id)';

-- Internship Logs
ALTER TABLE internship_logs ADD COLUMN IF NOT EXISTS is_deleted  TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว';
ALTER TABLE internship_logs ADD COLUMN IF NOT EXISTS deleted_at  DATETIME DEFAULT NULL COMMENT 'เวลาที่ลบ';
ALTER TABLE internship_logs ADD COLUMN IF NOT EXISTS deleted_by  INT DEFAULT NULL COMMENT 'ผู้ที่ทำการลบ (user_id)';

-- Evaluations
ALTER TABLE evaluations ADD COLUMN IF NOT EXISTS is_deleted  TINYINT DEFAULT 0 COMMENT '0=ปกติ, 1=ลบแล้ว';
ALTER TABLE evaluations ADD COLUMN IF NOT EXISTS deleted_at  DATETIME DEFAULT NULL COMMENT 'เวลาที่ลบ';
ALTER TABLE evaluations ADD COLUMN IF NOT EXISTS deleted_by  INT DEFAULT NULL COMMENT 'ผู้ที่ทำการลบ (user_id)';
