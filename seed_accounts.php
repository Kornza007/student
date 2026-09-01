<?php
require_once 'db.php';

echo "=== Seeding requested accounts ===\n";

// 1. Ensure Mentor "kan@jm.th"
$mEmail = 'kan@jm.th';
$mName = 'อาจารย์กานต์ จินดามัย';
$mDept = 'IT / พัฒนาระบบ';

$stmt = $conn->prepare("SELECT id FROM mentors WHERE email = ?");
$stmt->bind_param("s", $mEmail);
$stmt->execute();
$mentorRes = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($mentorRes) {
    $mentorId = $mentorRes['id'];
    echo "Found existing mentor ID: $mentorId\n";
} else {
    $stmt = $conn->prepare("INSERT INTO mentors (name, department, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $mName, $mDept, $mEmail);
    $stmt->execute();
    $mentorId = $stmt->insert_id;
    $stmt->close();
    echo "Created mentor ID: $mentorId\n";
}

// 2. Ensure Student "9745"
$sCode = '9745';
$sName = 'นายศิวกร เทคโนไทย';
$sUni = 'มหาวิทยาลัยเทคโนโลยี';
$sFaculty = 'วิทยาศาสตร์และเทคโนโลยี';
$sMajor = 'วิทยาการคอมพิวเตอร์';
$sPhone = '081-234-9745';
$sStartDate = date('Y-m-d', strtotime('-20 days'));
$sDuration = 90;

$stmt = $conn->prepare("SELECT id FROM students WHERE student_code = ?");
$stmt->bind_param("s", $sCode);
$stmt->execute();
$studentRes = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($studentRes) {
    $studentId = $studentRes['id'];
    echo "Found existing student ID: $studentId\n";
} else {
    $stmt = $conn->prepare("INSERT INTO students (student_code, name, major, university, faculty, phone, start_date, duration_days, mentor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssii", $sCode, $sName, $sMajor, $sUni, $sFaculty, $sPhone, $sStartDate, $sDuration, $mentorId);
    $stmt->execute();
    $studentId = $stmt->insert_id;
    $stmt->close();
    echo "Created student ID: $studentId\n";
}

// 3. Update Users Table with requested credentials
$accounts = [
    [
        'username' => 'admin',
        'password' => 'Admin01',
        'role'     => 'admin',
        'ref_id'   => null
    ],
    [
        'username' => 'kan@jm.th',
        'password' => '020252',
        'role'     => 'mentor',
        'ref_id'   => $mentorId
    ],
    [
        'username' => '9745',
        'password' => '081207',
        'role'     => 'student',
        'ref_id'   => $studentId
    ]
];

foreach ($accounts as $acc) {
    $hashedPassword = password_hash($acc['password'], PASSWORD_BCRYPT);
    $username = $acc['username'];
    $role = $acc['role'];
    $ref_id = $acc['ref_id'];

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $userRow = $check->get_result()->fetch_assoc();
    $check->close();

    if ($userRow) {
        $update = $conn->prepare("UPDATE users SET password = ?, role = ?, ref_id = ? WHERE username = ?");
        $update->bind_param("ssis", $hashedPassword, $role, $ref_id, $username);
        $update->execute();
        $update->close();
        echo "Updated account: $username (Role: $role)\n";
    } else {
        $insert = $conn->prepare("INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, ?, ?)");
        $insert->bind_param("sssi", $username, $hashedPassword, $role, $ref_id);
        $insert->execute();
        $insert->close();
        echo "Inserted account: $username (Role: $role)\n";
    }
}

// 4. Ensure some logs and evaluation for student 9745
$checkLogs = $conn->query("SELECT COUNT(*) as c FROM internship_logs WHERE student_id = $studentId")->fetch_assoc();
if ($checkLogs['c'] == 0) {
    $conn->query("INSERT INTO internship_logs (student_id, log_date, work_description, status, mentor_comment) VALUES 
        ($studentId, '" . date('Y-m-d', strtotime('-2 days')) . "', 'ศึกษาโครงสร้างฐานข้อมูลและระบบงาน ออกแบบ Flowchart', 'approved', 'ทำได้ดี มีความเข้าใจโครงสร้างระบบถูกต้อง'),
        ($studentId, '" . date('Y-m-d', strtotime('-1 days')) . "', 'พัฒนาหน้าจอ Frontend ส่วนแสดงผลตารางและตัวกรองข้อมูล', 'pending', NULL),
        ($studentId, '" . date('Y-m-d') . "', 'ทดสอบระบบการเข้าสู่ระบบและบันทึกงานประจำวัน', 'pending', NULL)
    ");
    echo "Seeded sample logs for student ID: $studentId\n";
}

$checkEval = $conn->query("SELECT COUNT(*) as c FROM evaluations WHERE student_id = $studentId")->fetch_assoc();
if ($checkEval['c'] == 0) {
    $conn->query("INSERT INTO evaluations (student_id, score_work, score_time, score_behavior, final_feedback) VALUES 
        ($studentId, 9, 10, 9, 'มีความตั้งใจสูง เรียนรู้งานได้รวดเร็ว และมีวินัยตรงต่อเวลาสม่ำเสมอ')
    ");
    echo "Seeded sample evaluation for student ID: $studentId\n";
}

echo "=== All done successfully ===\n";
?>
