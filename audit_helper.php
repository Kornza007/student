<?php
/**
 * audit_helper.php
 * ฟังก์ชันช่วยเหลือสำหรับระบบ Audit Log & Soft Delete
 * =====================================================
 * ไฟล์นี้ไม่แก้ไขไฟล์เดิมใดๆ ในโปรเจกต์
 * เรียกใช้ได้จาก audit_api.php เท่านั้น
 */

require_once __DIR__ . '/db.php';

// ====================================================
// 1) AUDIT LOG FUNCTIONS
// ====================================================

/**
 * กรองฟิลด์ password ออกจากข้อมูลเพื่อความเป็นส่วนตัว
 */
function sanitizeAuditData($data) {
    if (!is_array($data)) return $data;
    
    $sensitiveFields = ['password', 'passwd', 'pass', 'pwd', 'secret', 'token'];
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        $lowerKey = strtolower($key);
        $isSensitive = false;
        foreach ($sensitiveFields as $field) {
            if (strpos($lowerKey, $field) !== false) {
                $isSensitive = true;
                break;
            }
        }
        
        if ($isSensitive) {
            $sanitized[$key] = '******';
        } else {
            $sanitized[$key] = $value;
        }
    }
    
    return $sanitized;
}

/**
 * เขียน Audit Log ลงฐานข้อมูล
 */
function writeAuditLog($userId, $username, $userRole, $actionType, $tableName, $recordId, $oldValues = null, $newValues = null, $description = '') {
    global $conn;
    
    // กรอง password ออก
    if ($oldValues !== null) {
        $oldValues = sanitizeAuditData($oldValues);
    }
    if ($newValues !== null) {
        $newValues = sanitizeAuditData($newValues);
    }
    
    $oldJson = $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
    $newJson = $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, username, user_role, action_type, table_name, record_id, old_values, new_values, description, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issssissss",
        $userId, $username, $userRole, $actionType,
        $tableName, $recordId, $oldJson, $newJson,
        $description, $ipAddress
    );
    $stmt->execute();
    $logId = $stmt->insert_id;
    $stmt->close();
    
    return $logId;
}

/**
 * ดึงข้อมูล record ปัจจุบันก่อนดำเนินการ
 */
function getRecordBeforeAction($table, $id) {
    global $conn;
    
    $allowedTables = ['students', 'mentors', 'internship_logs', 'evaluations', 'users'];
    if (!in_array($table, $allowedTables, true)) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * ดึงชื่อที่แสดงผลของตาราง (สำหรับ UI)
 */
function getTableDisplayName($table) {
    $names = [
        'students'         => 'นักศึกษา',
        'mentors'          => 'พี่เลี้ยง',
        'internship_logs'  => 'บันทึกการฝึกงาน',
        'evaluations'      => 'การประเมินผล',
        'users'            => 'บัญชีผู้ใช้'
    ];
    return $names[$table] ?? $table;
}

/**
 * ดึงชื่อที่แสดงผลของ action (สำหรับ UI)
 */
function getActionDisplayName($action) {
    $names = [
        'CREATE'           => 'เพิ่มข้อมูล',
        'UPDATE'           => 'แก้ไขข้อมูล',
        'DELETE'           => 'ลบข้อมูล (Soft Delete)',
        'LOGIN'            => 'เข้าสู่ระบบ',
        'LOGOUT'           => 'ออกจากระบบ',
        'RESTORE'          => 'กู้คืนข้อมูล',
        'PERMANENT_DELETE' => 'ลบถาวร'
    ];
    return $names[$action] ?? $action;
}

// ====================================================
// 2) SOFT DELETE FUNCTIONS
// ====================================================

/**
 * Soft Delete — ตั้ง is_deleted = 1
 */
function softDelete($table, $id, $userId, $username, $userRole) {
    global $conn;
    
    $allowedTables = ['students', 'mentors', 'internship_logs', 'evaluations'];
    if (!in_array($table, $allowedTables, true)) {
        return ['success' => false, 'error' => 'ตารางไม่ถูกต้อง'];
    }
    
    // ดึงข้อมูลก่อนลบ
    $oldData = getRecordBeforeAction($table, $id);
    if (!$oldData) {
        return ['success' => false, 'error' => 'ไม่พบข้อมูลที่ต้องการลบ'];
    }
    
    // ตั้งค่า soft delete
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE `$table` SET is_deleted = 1, deleted_at = ?, deleted_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $now, $userId, $id);
    $stmt->execute();
    $stmt->close();
    
    // ถ้าเป็น students หรือ mentors ให้ soft delete user account ที่เกี่ยวข้องด้วย
    if ($table === 'students') {
        $conn->query("UPDATE users SET ref_id = ref_id WHERE role = 'student' AND ref_id = $id");
    }
    
    // สร้างคำอธิบาย
    $displayName = getTableDisplayName($table);
    $recordName = '';
    if ($table === 'students') {
        $recordName = ($oldData['name'] ?? '') . ' (' . ($oldData['student_code'] ?? '') . ')';
    } else if ($table === 'mentors') {
        $recordName = $oldData['name'] ?? '';
    } else if ($table === 'internship_logs') {
        $recordName = 'บันทึกวันที่ ' . ($oldData['log_date'] ?? '');
    } else if ($table === 'evaluations') {
        $recordName = 'การประเมินผล ID ' . $id;
    }
    
    $description = "ลบ{$displayName}: {$recordName}";
    
    // บันทึก Audit Log
    writeAuditLog($userId, $username, $userRole, 'DELETE', $table, $id, $oldData, null, $description);
    
    return ['success' => true, 'message' => "ลบข้อมูลเรียบร้อยแล้ว (ย้ายไปถังขยะ)"];
}

/**
 * กู้คืนข้อมูลจากถังขยะ
 */
function restoreRecord($table, $id, $userId, $username, $userRole) {
    global $conn;
    
    $allowedTables = ['students', 'mentors', 'internship_logs', 'evaluations'];
    if (!in_array($table, $allowedTables, true)) {
        return ['success' => false, 'error' => 'ตารางไม่ถูกต้อง'];
    }
    
    $stmt = $conn->prepare("UPDATE `$table` SET is_deleted = 0, deleted_at = NULL, deleted_by = NULL WHERE id = ? AND is_deleted = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'ไม่พบข้อมูลในถังขยะ'];
    }
    $stmt->close();
    
    $displayName = getTableDisplayName($table);
    $description = "กู้คืน{$displayName} ID: {$id}";
    
    writeAuditLog($userId, $username, $userRole, 'RESTORE', $table, $id, null, null, $description);
    
    return ['success' => true, 'message' => 'กู้คืนข้อมูลเรียบร้อยแล้ว'];
}

/**
 * ลบถาวร — DELETE FROM
 */
function permanentDelete($table, $id, $userId, $username, $userRole) {
    global $conn;
    
    $allowedTables = ['students', 'mentors', 'internship_logs', 'evaluations'];
    if (!in_array($table, $allowedTables, true)) {
        return ['success' => false, 'error' => 'ตารางไม่ถูกต้อง'];
    }
    
    // ดึงข้อมูลก่อนลบ
    $oldData = getRecordBeforeAction($table, $id);
    if (!$oldData) {
        return ['success' => false, 'error' => 'ไม่พบข้อมูลที่ต้องการลบ'];
    }
    
    // ลบข้อมูลที่เกี่ยวข้องอย่างสมบูรณ์
    if ($table === 'students') {
        // ลบ logs, evaluations และ user account ของนักศึกษาคนนี้
        $conn->query("DELETE FROM internship_logs WHERE student_id = $id");
        $conn->query("DELETE FROM evaluations WHERE student_id = $id");
        $delUser = $conn->prepare("DELETE FROM users WHERE role = 'student' AND ref_id = ?");
        $delUser->bind_param("i", $id);
        $delUser->execute();
        $delUser->close();
    } else if ($table === 'mentors') {
        // ปลด mentor_id ของนักศึกษาและ evaluations ให้อยู่ในสถานะว่าง
        $conn->query("UPDATE students SET mentor_id = NULL WHERE mentor_id = $id");
        $conn->query("UPDATE evaluations SET mentor_id = NULL WHERE mentor_id = $id");
        $delUser = $conn->prepare("DELETE FROM users WHERE role = 'mentor' AND ref_id = ?");
        $delUser->bind_param("i", $id);
        $delUser->execute();
        $delUser->close();
    }
    
    // ลบถาวร
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $displayName = getTableDisplayName($table);
    $description = "ลบถาวร{$displayName} ID: {$id}";
    
    writeAuditLog($userId, $username, $userRole, 'PERMANENT_DELETE', $table, $id, $oldData, null, $description);
    
    return ['success' => true, 'message' => 'ลบข้อมูลถาวรเรียบร้อยแล้ว'];
}

/**
 * ดึงรายการถังขยะ
 */
function getRecycleBinItems($table) {
    global $conn;
    
    $allowedTables = ['students', 'mentors', 'internship_logs', 'evaluations'];
    if (!in_array($table, $allowedTables, true)) {
        return [];
    }
    
    $query = "";
    if ($table === 'students') {
        $query = "SELECT s.*, m.name AS mentor_name, u.username AS deleted_by_username 
                  FROM students s 
                  LEFT JOIN mentors m ON s.mentor_id = m.id 
                  LEFT JOIN users u ON s.deleted_by = u.id
                  WHERE s.is_deleted = 1 
                  ORDER BY s.deleted_at DESC";
    } else if ($table === 'mentors') {
        $query = "SELECT m.*, u.username AS deleted_by_username 
                  FROM mentors m 
                  LEFT JOIN users u ON m.deleted_by = u.id
                  WHERE m.is_deleted = 1 
                  ORDER BY m.deleted_at DESC";
    } else if ($table === 'internship_logs') {
        $query = "SELECT il.*, s.name AS student_name, s.student_code, u.username AS deleted_by_username 
                  FROM internship_logs il 
                  LEFT JOIN students s ON il.student_id = s.id
                  LEFT JOIN users u ON il.deleted_by = u.id
                  WHERE il.is_deleted = 1 
                  ORDER BY il.deleted_at DESC";
    } else if ($table === 'evaluations') {
        $query = "SELECT e.*, s.name AS student_name, s.student_code, u.username AS deleted_by_username 
                  FROM evaluations e 
                  LEFT JOIN students s ON e.student_id = s.id
                  LEFT JOIN users u ON e.deleted_by = u.id
                  WHERE e.is_deleted = 1 
                  ORDER BY e.deleted_at DESC";
    }
    
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * นับจำนวนรายการในถังขยะ
 */
function getRecycleBinCounts() {
    global $conn;
    
    $counts = [];
    $tables = ['students', 'mentors', 'internship_logs', 'evaluations'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM `$table` WHERE is_deleted = 1");
        $row = $result ? $result->fetch_assoc() : ['cnt' => 0];
        $counts[$table] = (int)$row['cnt'];
    }
    
    $counts['total'] = array_sum($counts);
    return $counts;
}
?>
