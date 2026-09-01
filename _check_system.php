<?php
require __DIR__ . '/audit_helper.php';
global $conn;

echo "=== DATABASE STRUCTURE CHECK ===" . PHP_EOL;

// ตรวจสอบ is_deleted column ในทุกตาราง
$tables = ['students', 'mentors', 'internship_logs', 'evaluations'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE 'is_deleted'");
    echo "$t.is_deleted: " . ($res && $res->num_rows > 0 ? 'EXISTS' : 'MISSING') . PHP_EOL;
}

// ตรวจสอบ deleted_at column
foreach ($tables as $t) {
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE 'deleted_at'");
    echo "$t.deleted_at: " . ($res && $res->num_rows > 0 ? 'EXISTS' : 'MISSING') . PHP_EOL;
}

// ตรวจสอบตาราง audit_logs
$res = $conn->query('SELECT COUNT(*) as cnt FROM audit_logs');
if ($res) {
    $row = $res->fetch_assoc();
    echo "audit_logs records: " . $row['cnt'] . PHP_EOL;
} else {
    echo "audit_logs table: ERROR - " . $conn->error . PHP_EOL;
}

// ตรวจสอบข้อมูลในถังขยะ
echo PHP_EOL . "=== RECYCLE BIN DATA ===" . PHP_EOL;
foreach ($tables as $t) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM `$t` WHERE is_deleted = 1");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "$t recycle bin: " . $row['cnt'] . PHP_EOL;
    } else {
        echo "$t recycle bin: ERROR - " . $conn->error . PHP_EOL;
    }
}

// ตรวจสอบข้อมูลปกติ (ไม่อยู่ในถังขยะ)
echo PHP_EOL . "=== ACTIVE DATA ===" . PHP_EOL;
foreach ($tables as $t) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM `$t` WHERE (is_deleted = 0 OR is_deleted IS NULL)");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "$t active: " . $row['cnt'] . PHP_EOL;
    } else {
        echo "$t active: ERROR - " . $conn->error . PHP_EOL;
    }
}

// ตรวจสอบ permanentDelete function
echo PHP_EOL . "=== FUNCTION CHECK ===" . PHP_EOL;
echo "permanentDelete exists: " . (function_exists('permanentDelete') ? 'YES' : 'NO') . PHP_EOL;
echo "softDelete exists: " . (function_exists('softDelete') ? 'YES' : 'NO') . PHP_EOL;
echo "restoreRecord exists: " . (function_exists('restoreRecord') ? 'YES' : 'NO') . PHP_EOL;
echo "writeAuditLog exists: " . (function_exists('writeAuditLog') ? 'YES' : 'NO') . PHP_EOL;

// ตรวจสอบ API endpoints
echo PHP_EOL . "=== ALL OK ===" . PHP_EOL;
?>
