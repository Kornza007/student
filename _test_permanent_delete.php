<?php
require __DIR__ . '/audit_helper.php';
global $conn;

echo "=== TEST PERMANENT DELETE ===" . PHP_EOL;

// ดูข้อมูลในถังขยะ
$res = $conn->query("SELECT id, name FROM students WHERE is_deleted = 1 LIMIT 5");
$binItems = [];
while ($row = $res->fetch_assoc()) {
    $binItems[] = $row;
    echo "In recycle bin: ID={$row['id']}, name={$row['name']}" . PHP_EOL;
}

if (count($binItems) === 0) {
    echo "No items in recycle bin to test!" . PHP_EOL;
    exit;
}

// เลือก item แรก
$testId = $binItems[0]['id'];
$testName = $binItems[0]['name'];
echo PHP_EOL . "--- Testing permanent delete on ID=$testId ($testName) ---" . PHP_EOL;

// ก่อนลบ
$before = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE id = $testId")->fetch_assoc();
echo "Before delete - Record exists: " . ($before['cnt'] > 0 ? 'YES' : 'NO') . PHP_EOL;

// เรียก permanentDelete
$result = permanentDelete('students', $testId, 1, 'admin', 'admin');
echo "Result: " . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;

// หลังลบ
$after = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE id = $testId")->fetch_assoc();
echo "After delete - Record exists: " . ($after['cnt'] > 0 ? 'YES (FAIL!)' : 'NO (SUCCESS!)') . PHP_EOL;

// ตรวจ audit log
$logCheck = $conn->query("SELECT log_id, action_type, description, created_at FROM audit_logs WHERE action_type = 'PERMANENT_DELETE' ORDER BY log_id DESC LIMIT 1");
if ($logCheck && $logCheck->num_rows > 0) {
    $log = $logCheck->fetch_assoc();
    echo "Audit log: ID={$log['log_id']}, action={$log['action_type']}, desc={$log['description']}" . PHP_EOL;
} else {
    echo "WARNING: No PERMANENT_DELETE audit log found!" . PHP_EOL;
}

// ตรวจ user account cascade
$userCheck = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student' AND ref_id = $testId")->fetch_assoc();
echo "User account cascade: " . ($userCheck['cnt'] > 0 ? 'STILL EXISTS' : 'DELETED OK') . PHP_EOL;

// สรุปถังขยะ
$remaining = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE is_deleted = 1")->fetch_assoc();
echo "Students remaining in recycle bin: " . $remaining['cnt'] . PHP_EOL;

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
