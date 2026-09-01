<?php
/**
 * audit_api.php
 * API Endpoints สำหรับระบบ Audit Log & Soft Delete
 * =====================================================
 * เข้าถึงได้เฉพาะ Admin เท่านั้น (ยกเว้น soft_delete ที่ทุก role ใช้ได้)
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/audit_helper.php';

function auditRespond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function auditGetJsonBody() {
    $raw = file_get_contents("php://input");
    return ($raw === '' || $raw === false) ? [] : json_decode($raw, true);
}

function requireAdmin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        auditRespond(["success" => false, "error" => "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้"], 403);
    }
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        auditRespond(["success" => false, "error" => "กรุณาเข้าสู่ระบบก่อน"], 401);
    }
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

$action = $_GET['action'] ?? '';

try {
    // =========================================================
    // 1) GET AUDIT LOGS (Admin Only)
    // =========================================================
    if ($action === 'get_audit_logs') {
        requireAdmin();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;
        
        // Filters
        $dateFrom   = $_GET['date_from'] ?? '';
        $dateTo     = $_GET['date_to'] ?? '';
        $actionType = $_GET['action_type'] ?? '';
        $tableName  = $_GET['table_name'] ?? '';
        $userId     = (int)($_GET['user_id'] ?? 0);
        $search     = trim($_GET['search'] ?? '');
        
        $where = "1=1";
        $params = [];
        $types = "";
        
        if (!empty($dateFrom)) {
            $where .= " AND DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        if (!empty($dateTo)) {
            $where .= " AND DATE(al.created_at) <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }
        if (!empty($actionType)) {
            $where .= " AND al.action_type = ?";
            $params[] = $actionType;
            $types .= "s";
        }
        if (!empty($tableName)) {
            $where .= " AND al.table_name = ?";
            $params[] = $tableName;
            $types .= "s";
        }
        if ($userId > 0) {
            $where .= " AND al.user_id = ?";
            $params[] = $userId;
            $types .= "i";
        }
        if (!empty($search)) {
            $where .= " AND (al.description LIKE ? OR al.username LIKE ?)";
            $searchLike = "%{$search}%";
            $params[] = $searchLike;
            $params[] = $searchLike;
            $types .= "ss";
        }
        
        // Count total
        $countSql = "SELECT COUNT(*) AS total FROM audit_logs al WHERE $where";
        if (!empty($params)) {
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            $countStmt->close();
        } else {
            $total = $conn->query($countSql)->fetch_assoc()['total'];
        }
        
        // Fetch logs
        $sql = "SELECT al.* FROM audit_logs al WHERE $where ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $fetchParams = array_merge($params, [$limit, $offset]);
        $fetchTypes = $types . "ii";
        
        $stmt = $conn->prepare($sql);
        if (!empty($fetchParams)) {
            $stmt->bind_param($fetchTypes, ...$fetchParams);
        }
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Enrich with display names
        foreach ($logs as &$log) {
            $log['action_display'] = getActionDisplayName($log['action_type']);
            $log['table_display'] = getTableDisplayName($log['table_name'] ?? '');
            $log['old_values'] = $log['old_values'] ? json_decode($log['old_values'], true) : null;
            $log['new_values'] = $log['new_values'] ? json_decode($log['new_values'], true) : null;
        }
        
        // Get distinct users for filter dropdown
        $usersResult = $conn->query("SELECT DISTINCT user_id, username, user_role FROM audit_logs WHERE user_id IS NOT NULL ORDER BY username");
        $distinctUsers = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];
        
        auditRespond([
            "success" => true,
            "logs" => $logs,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => max(1, ceil($total / $limit)),
                "total_records" => (int)$total,
                "per_page" => $limit
            ],
            "distinct_users" => $distinctUsers
        ]);
    }
    
    // =========================================================
    // 2) GET AUDIT DETAIL + RECORD HISTORY (Admin Only)
    // =========================================================
    if ($action === 'get_audit_detail') {
        requireAdmin();
        
        $logId = (int)($_GET['log_id'] ?? 0);
        if ($logId <= 0) {
            auditRespond(["success" => false, "error" => "ระบุ log_id ไม่ถูกต้อง"], 400);
        }
        
        // ดึง log หลัก
        $stmt = $conn->prepare("SELECT * FROM audit_logs WHERE log_id = ?");
        $stmt->bind_param("i", $logId);
        $stmt->execute();
        $log = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$log) {
            auditRespond(["success" => false, "error" => "ไม่พบ log นี้"], 404);
        }
        
        $log['action_display'] = getActionDisplayName($log['action_type']);
        $log['table_display'] = getTableDisplayName($log['table_name'] ?? '');
        $log['old_values'] = $log['old_values'] ? json_decode($log['old_values'], true) : null;
        $log['new_values'] = $log['new_values'] ? json_decode($log['new_values'], true) : null;
        
        // ดึงประวัติทั้งหมดของ record นี้ (สำหรับกรณี DELETE — แสดงว่าเคยแก้ไขอะไรมาก่อน)
        $history = [];
        if (!empty($log['table_name']) && !empty($log['record_id'])) {
            $hStmt = $conn->prepare("SELECT * FROM audit_logs WHERE table_name = ? AND record_id = ? ORDER BY created_at ASC");
            $hStmt->bind_param("si", $log['table_name'], $log['record_id']);
            $hStmt->execute();
            $hResult = $hStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $hStmt->close();
            
            foreach ($hResult as $h) {
                $h['action_display'] = getActionDisplayName($h['action_type']);
                $h['old_values'] = $h['old_values'] ? json_decode($h['old_values'], true) : null;
                $h['new_values'] = $h['new_values'] ? json_decode($h['new_values'], true) : null;
                $history[] = $h;
            }
        }
        
        auditRespond([
            "success" => true,
            "log" => $log,
            "record_history" => $history
        ]);
    }
    
    // =========================================================
    // 3) SOFT DELETE (ทุก role ที่มีสิทธิ์ใช้ได้)
    // =========================================================
    if ($action === 'soft_delete') {
        requireLogin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        
        $table = $data['table'] ?? '';
        $id = (int)($data['id'] ?? 0);
        
        if ($table === 'logs') $table = 'internship_logs';
        
        if (empty($table) || $id <= 0) {
            auditRespond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }
        
        // ตรวจสอบสิทธิ์
        if (in_array($table, ['students', 'mentors', 'evaluations'])) {
            if ($user['role'] !== 'admin') {
                auditRespond(["success" => false, "error" => "คุณไม่มีสิทธิ์ลบข้อมูลนี้"], 403);
            }
        }
        
        if ($table === 'internship_logs' && $user['role'] === 'student') {
            $checkStmt = $conn->prepare("SELECT student_id FROM internship_logs WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $logOwner = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if (!$logOwner || $logOwner['student_id'] != $user['ref_id']) {
                auditRespond(["success" => false, "error" => "คุณไม่มีสิทธิ์ลบบันทึกนี้"], 403);
            }
        }
        
        $result = softDelete($table, $id, $user['id'], $user['username'], $user['role']);
        auditRespond($result, $result['success'] ? 200 : 400);
    }
    
    // =========================================================
    // 4) RESTORE FROM RECYCLE BIN (Admin Only)
    // =========================================================
    if ($action === 'restore_item') {
        requireAdmin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        
        $table = $data['table'] ?? '';
        $id = (int)($data['id'] ?? 0);
        
        if (empty($table) || $id <= 0) {
            auditRespond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }
        
        $result = restoreRecord($table, $id, $user['id'], $user['username'], $user['role']);
        auditRespond($result, $result['success'] ? 200 : 400);
    }
    
    // =========================================================
    // 5) PERMANENT DELETE (Admin Only)
    // =========================================================
    if ($action === 'permanent_delete') {
        requireAdmin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        
        $table = $data['table'] ?? '';
        $id = (int)($data['id'] ?? 0);
        
        if (empty($table) || $id <= 0) {
            auditRespond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }
        
        $result = permanentDelete($table, $id, $user['id'], $user['username'], $user['role']);
        auditRespond($result, $result['success'] ? 200 : 400);
    }
    
    // =========================================================
    // 6) BULK PERMANENT DELETE (Admin Only)
    // =========================================================
    if ($action === 'bulk_permanent_delete') {
        requireAdmin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        
        $table = $data['table'] ?? '';
        $ids = $data['ids'] ?? [];
        
        if (empty($table) || !is_array($ids) || count($ids) === 0) {
            auditRespond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            
            $result = permanentDelete($table, $id, $user['id'], $user['username'], $user['role']);
            if ($result['success']) {
                $deletedCount++;
            } else {
                $errors[] = "ID {$id}: " . $result['error'];
            }
        }
        
        auditRespond([
            "success" => true,
            "message" => "ลบถาวรสำเร็จ {$deletedCount} รายการ",
            "deleted_count" => $deletedCount,
            "errors" => $errors
        ]);
    }
    
    // =========================================================
    // 7) EMPTY RECYCLE BIN (Admin Only)
    // =========================================================
    if ($action === 'empty_recycle_bin') {
        requireAdmin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        
        $table = $data['table'] ?? 'all';
        
        $tables = ($table === 'all') 
            ? ['students', 'mentors', 'internship_logs', 'evaluations'] 
            : [$table];
        
        $totalDeleted = 0;
        
        foreach ($tables as $tbl) {
            $allowedTables = ['students', 'mentors', 'internship_logs', 'evaluations'];
            if (!in_array($tbl, $allowedTables, true)) continue;
            
            // ดึง IDs ที่ is_deleted = 1
            $idsResult = $conn->query("SELECT id FROM `$tbl` WHERE is_deleted = 1");
            if ($idsResult) {
                while ($row = $idsResult->fetch_assoc()) {
                    permanentDelete($tbl, $row['id'], $user['id'], $user['username'], $user['role']);
                    $totalDeleted++;
                }
            }
        }
        
        auditRespond([
            "success" => true,
            "message" => "ล้างถังขยะเรียบร้อย ลบถาวร {$totalDeleted} รายการ",
            "deleted_count" => $totalDeleted
        ]);
    }
    
    // =========================================================
    // 8) GET RECYCLE BIN ITEMS (Admin Only)
    // =========================================================
    if ($action === 'get_recycle_bin') {
        requireAdmin();
        
        $table = $_GET['table'] ?? 'students';
        
        $items = getRecycleBinItems($table);
        $counts = getRecycleBinCounts();
        
        auditRespond([
            "success" => true,
            "items" => $items,
            "counts" => $counts,
            "current_table" => $table
        ]);
    }
    
    // =========================================================
    // 9) GET STATS SUMMARY (Admin Only)
    // =========================================================
    if ($action === 'get_audit_stats') {
        requireAdmin();
        
        // สถิติ audit logs วันนี้
        $today = date('Y-m-d');
        $todayStats = $conn->query("
            SELECT 
                COUNT(*) as total_today,
                SUM(CASE WHEN action_type = 'CREATE' THEN 1 ELSE 0 END) as creates,
                SUM(CASE WHEN action_type = 'UPDATE' THEN 1 ELSE 0 END) as updates,
                SUM(CASE WHEN action_type = 'DELETE' THEN 1 ELSE 0 END) as deletes,
                SUM(CASE WHEN action_type = 'LOGIN' THEN 1 ELSE 0 END) as logins
            FROM audit_logs 
            WHERE DATE(created_at) = '$today'
        ")->fetch_assoc();
        
        // สถิติรวมทั้งหมด
        $allStats = $conn->query("SELECT COUNT(*) as total_all FROM audit_logs")->fetch_assoc();
        
        // ผู้ใช้ที่ active ล่าสุด
        $recentUsers = $conn->query("
            SELECT username, user_role, MAX(created_at) as last_action 
            FROM audit_logs 
            WHERE user_id IS NOT NULL
            GROUP BY user_id, username, user_role
            ORDER BY last_action DESC 
            LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);
        
        $recycleCounts = getRecycleBinCounts();
        
        auditRespond([
            "success" => true,
            "today" => $todayStats,
            "all_time" => $allStats,
            "recent_users" => $recentUsers,
            "recycle_bin" => $recycleCounts
        ]);
    }
    
    // =========================================================
    // 10) BULK RESTORE (Admin Only)
    // =========================================================
    if ($action === 'bulk_restore') {
        requireAdmin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        
        $table = $data['table'] ?? '';
        $ids = $data['ids'] ?? [];
        
        if (empty($table) || !is_array($ids) || count($ids) === 0) {
            auditRespond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }
        
        $restoredCount = 0;
        
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            
            $result = restoreRecord($table, $id, $user['id'], $user['username'], $user['role']);
            if ($result['success']) {
                $restoredCount++;
            }
        }
        
        auditRespond([
            "success" => true,
            "message" => "กู้คืนสำเร็จ {$restoredCount} รายการ",
            "restored_count" => $restoredCount
        ]);
    }

    // =========================================================
    // 11) CLEAR AUDIT LOGS (Admin Only)
    // =========================================================
    if ($action === 'clear_audit_logs') {
        requireAdmin();
        $user = getCurrentUser();
        $data = auditGetJsonBody();
        $days = (int)($data['days'] ?? 0); // 0 = all, > 0 = older than X days
        
        if ($days > 0) {
            $stmt = $conn->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $deletedCount = $stmt->affected_rows;
            $stmt->close();
            $msg = "ลบประวัติการใช้งานที่เก่ากว่า {$days} วัน สำเร็จ {$deletedCount} รายการ";
        } else {
            $conn->query("TRUNCATE TABLE audit_logs");
            $deletedCount = 0;
            $msg = "ล้างประวัติการใช้งานทั้งหมดเรียบร้อยแล้ว";
        }
        
        // บันทึกกิจกรรมการล้างประวัติ
        writeAuditLog($user['id'], $user['username'], $user['role'], 'PERMANENT_DELETE', 'audit_logs', 0, null, null, $msg);
        
        auditRespond([
            "success" => true,
            "message" => $msg,
            "deleted_count" => $deletedCount
        ]);
    }

} catch (Throwable $e) {
    auditRespond(["success" => false, "error" => $e->getMessage()], 500);
}
?>
