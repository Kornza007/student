<?php
// api.php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';
require_once 'audit_helper.php';

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody() {
    $raw = file_get_contents("php://input");
    return ($raw === '' || $raw === false) ? [] : json_decode($raw, true);
}

function checkRole($allowedRoles = []) {
    if (!isset($_SESSION['user'])) {
        respond(["success" => false, "error" => "กรุณาเข้าสู่ระบบก่อนใช้งาน"], 401);
    }
    
    $userRole = $_SESSION['user']['role'];
    if (!in_array($userRole, $allowedRoles, true)) {
        respond(["success" => false, "error" => "คุณไม่มีสิทธิ์ใช้งานในส่วนนี้"], 403);
    }
}

// ฟังก์ชันดึงข้อมูลผู้ใช้พร้อมชื่อจริง
function fetchUserDataWithDisplayName($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, username, role, ref_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) return null;

    $displayName = $user['username'];
    if ($user['role'] === 'mentor' && !empty($user['ref_id'])) {
        $mStmt = $conn->prepare("SELECT name FROM mentors WHERE id = ?");
        $mStmt->bind_param("i", $user['ref_id']);
        $mStmt->execute();
        $mRes = $mStmt->get_result()->fetch_assoc();
        if ($mRes && !empty($mRes['name'])) {
            $displayName = $mRes['name'];
        }
        $mStmt->close();
    } else if ($user['role'] === 'student' && !empty($user['ref_id'])) {
        $sStmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
        $sStmt->bind_param("i", $user['ref_id']);
        $sStmt->execute();
        $sRes = $sStmt->get_result()->fetch_assoc();
        if ($sRes && !empty($sRes['name'])) {
            $displayName = $sRes['name'];
        }
        $sStmt->close();
    } else if ($user['role'] === 'admin') {
        $displayName = "ผู้ดูแลระบบ (Admin)";
    }

    $user['display_name'] = $displayName;
    return $user;
}

$action = $_GET['action'] ?? '';

try {
    // ---------------------------------------------------------
    // 1) AUTHENTICATION & PASSWORD MANAGEMENT
    // ---------------------------------------------------------
    if ($action === 'login') {
        $data = getJsonBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        if ($username === '' || $password === '') {
            respond(["success" => false, "error" => "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน"], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $fullUserData = fetchUserDataWithDisplayName($conn, $user['id']);
            $_SESSION['user'] = $fullUserData;

            // บันทึก Audit Log เมื่อ Login สำเร็จ
            writeAuditLog(
                $fullUserData['id'],
                $fullUserData['username'],
                $fullUserData['role'],
                'LOGIN',
                'users',
                $fullUserData['id'],
                null,
                ['role' => $fullUserData['role'], 'display_name' => $fullUserData['display_name']],
                "ผู้ใช้ {$fullUserData['username']} ({$fullUserData['role']}) เข้าสู่ระบบสำเร็จ"
            );

            respond(["success" => true, "user" => $_SESSION['user']]);
        } else {
            respond(["success" => false, "error" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"], 401);
        }
    }

    if ($action === 'logout') {
        if (isset($_SESSION['user'])) {
            writeAuditLog(
                $_SESSION['user']['id'],
                $_SESSION['user']['username'],
                $_SESSION['user']['role'],
                'LOGOUT',
                'users',
                $_SESSION['user']['id'],
                null,
                null,
                "ผู้ใช้ {$_SESSION['user']['username']} ({$_SESSION['user']['role']}) ออกจากระบบ"
            );
        }
        session_unset();
        session_destroy();
        respond(["success" => true]);
    }

    if ($action === 'check_session' || $action === 'get_current_user') {
        if (isset($_SESSION['user']['id'])) {
            $fullUserData = fetchUserDataWithDisplayName($conn, $_SESSION['user']['id']);
            if ($fullUserData) {
                $_SESSION['user'] = $fullUserData;
                respond($_SESSION['user']);
            } else {
                respond(null, 401);
            }
        } else {
            respond(null, 401);
        }
    }

    if ($action === 'change_password') {
        if (!isset($_SESSION['user'])) {
            respond(["success" => false, "error" => "กรุณาเข้าสู่ระบบก่อนใช้งาน"], 401);
        }

        $data = getJsonBody();
        $oldPassword = trim($data['old_password'] ?? '');
        $newPassword = trim($data['new_password'] ?? '');
        $userId = $_SESSION['user']['id'];

        if (empty($oldPassword) || empty($newPassword)) {
            respond(["success" => false, "error" => "กรุณากรอกรหัสผ่านเดิมและรหัสผ่านใหม่"], 400);
        }

        if (strlen($newPassword) < 6) {
            respond(["success" => false, "error" => "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร"], 400);
        }

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($oldPassword, $user['password'])) {
            $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHashedPassword, $userId);
            $updateStmt->execute();
            $updateStmt->close();

            writeAuditLog(
                $userId,
                $_SESSION['user']['username'],
                $_SESSION['user']['role'],
                'UPDATE',
                'users',
                $userId,
                null,
                null,
                "ผู้ใช้ {$_SESSION['user']['username']} เปลี่ยนรหัสผ่านของตนเองสำเร็จ"
            );

            respond(["success" => true, "message" => "เปลี่ยนรหัสผ่านสำเร็จแล้ว"]);
        } else {
            respond(["success" => false, "error" => "รหัสผ่านเดิมไม่ถูกต้อง"], 400);
        }
    }

    if ($action === 'admin_reset_password') {
        checkRole(['admin']);

        $data = getJsonBody();
        $targetRole = $data['role'] ?? '';
        $targetRefId = (int)($data['ref_id'] ?? 0);
        $newPassword = trim($data['new_password'] ?? '');

        if (empty($targetRole) || $targetRefId <= 0 || empty($newPassword)) {
            respond(["success" => false, "error" => "ข้อมูลพารามิเตอร์ไม่ถูกต้อง"], 400);
        }

        if (strlen($newPassword) < 6) {
            respond(["success" => false, "error" => "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร"], 400);
        }

        $findTarget = $conn->prepare("SELECT id, username FROM users WHERE role = ? AND ref_id = ?");
        $findTarget->bind_param("si", $targetRole, $targetRefId);
        $findTarget->execute();
        $targetUser = $findTarget->get_result()->fetch_assoc();
        $findTarget->close();
        $targetName = $targetUser ? $targetUser['username'] : "$targetRole (ID $targetRefId)";
        $targetUserId = $targetUser ? (int)$targetUser['id'] : $targetRefId;

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE role = ? AND ref_id = ?");
        $stmt->bind_param("ssi", $hashedPassword, $targetRole, $targetRefId);

        if ($stmt->execute()) {
            $stmt->close();

            writeAuditLog(
                $_SESSION['user']['id'],
                $_SESSION['user']['username'],
                $_SESSION['user']['role'],
                'UPDATE',
                'users',
                $targetUserId,
                null,
                ['role' => $targetRole, 'ref_id' => $targetRefId],
                "ผู้ดูแลระบบรีเซ็ตรหัสผ่านให้กับผู้ใช้ {$targetName} (Role: {$targetRole})"
            );

            respond(["success" => true, "message" => "แก้ไขรหัสผ่านเสร็จสิ้น"]);
        } else {
            $stmt->close();
            respond(["success" => false, "error" => "ไม่สามารถเปลี่ยนรหัสผ่านได้"], 500);
        }
    }

    // ---------------------------------------------------------
    // 2) MENTORS MANAGEMENT
    // ---------------------------------------------------------
    if ($action === 'get_mentors') {
        checkRole(['admin', 'mentor', 'student']);
        $user = $_SESSION['user'];

        if ($user['role'] === 'mentor') {
            $stmt = $conn->prepare("SELECT * FROM mentors WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
            $stmt->bind_param("i", $user['ref_id']);
            $stmt->execute();
            respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            $result = $conn->query("SELECT * FROM mentors WHERE (is_deleted = 0 OR is_deleted IS NULL) ORDER BY id DESC");
            respond($result->fetch_all(MYSQLI_ASSOC));
        }
    }

    if ($action === 'add_mentor') {
        checkRole(['admin']);
        $data = getJsonBody();
        
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $department = trim($data['department'] ?? '');

        if (empty($name) || empty($email)) {
            respond(["success" => false, "error" => "กรุณากรอกชื่อและอีเมล"], 400);
        }

        $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->bind_param("s", $email);
        $checkUser->execute();
        if ($checkUser->get_result()->num_rows > 0) {
            respond(["success" => false, "error" => "อีเมลนี้ถูกใช้งานเป็น Username ในระบบแล้ว"], 400);
        }
        $checkUser->close();

        $stmt = $conn->prepare("INSERT INTO mentors (name, email, department) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $department);
        $stmt->execute();
        $mentor_id = $stmt->insert_id;
        $stmt->close();

        $defaultPassword = password_hash("123456", PASSWORD_BCRYPT);
        $stmtUser = $conn->prepare("INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, 'mentor', ?)");
        $stmtUser->bind_param("ssi", $email, $defaultPassword, $mentor_id);
        $stmtUser->execute();
        $stmtUser->close();

        writeAuditLog(
            $_SESSION['user']['id'],
            $_SESSION['user']['username'],
            $_SESSION['user']['role'],
            'CREATE',
            'mentors',
            $mentor_id,
            null,
            ['name' => $name, 'email' => $email, 'department' => $department],
            "เพิ่มข้อมูลพี่เลี้ยงใหม่: {$name} ({$department})"
        );

        respond(["success" => true, "id" => $mentor_id, "message" => "สร้างข้อมูลพี่เลี้ยงและสร้างบัญชีล็อกอินสำเร็จ"]);
    }

    if ($action === 'update_mentor') {
        checkRole(['admin']);
        $data = getJsonBody();
        $mentorId = (int)($data['id'] ?? 0);
        $oldMentor = getRecordBeforeAction('mentors', $mentorId);

        $stmt = $conn->prepare("UPDATE mentors SET name = ?, email = ?, department = ? WHERE id = ?");
        $stmt->bind_param("sssi", $data['name'], $data['email'], $data['department'], $mentorId);
        $stmt->execute();
        $stmt->close();

        writeAuditLog(
            $_SESSION['user']['id'],
            $_SESSION['user']['username'],
            $_SESSION['user']['role'],
            'UPDATE',
            'mentors',
            $mentorId,
            $oldMentor,
            ['name' => $data['name'], 'email' => $data['email'], 'department' => $data['department']],
            "แก้ไขข้อมูลพี่เลี้ยง: {$data['name']}"
        );

        respond(["success" => true]);
    }

    if ($action === 'create_user') {
        checkRole(['admin']);
        $data = getJsonBody();
        
        $username = trim($data['username'] ?? '');
        $rawPassword = trim($data['password'] ?? '');
        $role = $data['role'] ?? '';
        $ref_id = !empty($data['ref_id']) ? (int)$data['ref_id'] : null;

        if (empty($username) || empty($rawPassword) || empty($role)) {
            respond(["success" => false, "error" => "กรุณากรอกข้อมูลให้ครบถ้วน"], 400);
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            respond(["success" => false, "error" => "ชื่อผู้ใช้นี้มีในระบบแล้ว"], 400);
        }
        $stmt->close();

        $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $hashedPassword, $role, $ref_id);
        
        if ($stmt->execute()) {
            $newUid = $stmt->insert_id;
            $stmt->close();

            writeAuditLog(
                $_SESSION['user']['id'],
                $_SESSION['user']['username'],
                $_SESSION['user']['role'],
                'CREATE',
                'users',
                $newUid,
                null,
                ['username' => $username, 'role' => $role, 'ref_id' => $ref_id],
                "สร้างบัญชีผู้ใช้ใหม่: {$username} ({$role})"
            );

            respond(["success" => true, "message" => "สร้างบัญชีผู้ใช้เรียบร้อยแล้ว"]);
        } else {
            respond(["success" => false, "error" => "ไม่สามารถสร้างบัญชีได้"], 500);
        }
    }

    // ---------------------------------------------------------
    // 3) STUDENTS MANAGEMENT
    // ---------------------------------------------------------
    if ($action === 'get_students') {
        checkRole(['admin', 'mentor', 'student']);
        $user = $_SESSION['user'];

        if ($user['role'] === 'mentor') {
            $stmt = $conn->prepare("SELECT students.*, mentors.name AS mentor_name FROM students LEFT JOIN mentors ON students.mentor_id = mentors.id WHERE students.mentor_id = ? AND (students.is_deleted = 0 OR students.is_deleted IS NULL) ORDER BY students.id DESC");
            $stmt->bind_param("i", $user['ref_id']);
            $stmt->execute();
            respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else if ($user['role'] === 'student') {
            $stmt = $conn->prepare("SELECT students.*, mentors.name AS mentor_name FROM students LEFT JOIN mentors ON students.mentor_id = mentors.id WHERE students.id = ? AND (students.is_deleted = 0 OR students.is_deleted IS NULL)");
            $stmt->bind_param("i", $user['ref_id']);
            $stmt->execute();
            respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            $result = $conn->query("SELECT students.*, mentors.name AS mentor_name FROM students LEFT JOIN mentors ON students.mentor_id = mentors.id WHERE (students.is_deleted = 0 OR students.is_deleted IS NULL) ORDER BY students.id DESC");
            respond($result->fetch_all(MYSQLI_ASSOC));
        }
    }

    if ($action === 'add_student') {
        checkRole(['admin']); // เฉพาะ Admin
        $data = getJsonBody();
        $studentCode = trim($data['student_code'] ?? '');
        $startDate = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
        $duration = !empty($data['duration_days']) ? (int)$data['duration_days'] : 90;
        $mentorId = !empty($data['mentor_id']) ? (int)$data['mentor_id'] : null;

        if (empty($studentCode)) {
            respond(["success" => false, "error" => "กรุณากรอกรหัสนักศึกษา"], 400);
        }

        $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->bind_param("s", $studentCode);
        $checkUser->execute();
        if ($checkUser->get_result()->num_rows > 0) {
            respond(["success" => false, "error" => "รหัสนักศึกษานี้ถูกใช้งานเป็น Username ในระบบแล้ว"], 400);
        }
        $checkUser->close();

        $stmt = $conn->prepare("INSERT INTO students (student_code, name, major, university, faculty, phone, start_date, duration_days, mentor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssii", $studentCode, $data['name'], $data['major'], $data['university'], $data['faculty'], $data['phone'], $startDate, $duration, $mentorId);
        $stmt->execute();
        $student_id = $stmt->insert_id;
        $stmt->close();

        $defaultPassword = password_hash("123456", PASSWORD_BCRYPT);
        $stmtUser = $conn->prepare("INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, 'student', ?)");
        $stmtUser->bind_param("ssi", $studentCode, $defaultPassword, $student_id);
        $stmtUser->execute();
        $stmtUser->close();

        writeAuditLog(
            $_SESSION['user']['id'],
            $_SESSION['user']['username'],
            $_SESSION['user']['role'],
            'CREATE',
            'students',
            $student_id,
            null,
            ['student_code' => $studentCode, 'name' => $data['name'], 'major' => $data['major'], 'university' => $data['university'], 'mentor_id' => $mentorId],
            "เพิ่มข้อมูลนักศึกษาใหม่: {$data['name']} (รหัส: {$studentCode})"
        );

        respond(["success" => true, "id" => $student_id, "message" => "สร้างข้อมูลนักศึกษาและสร้างบัญชีล็อกอินสำเร็จ"]);
    }

    if ($action === 'update_student') {
        checkRole(['admin']);
        $data = getJsonBody();
        $studentId = (int)($data['id'] ?? 0);
        $oldStudent = getRecordBeforeAction('students', $studentId);

        $startDate = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
        $duration = !empty($data['duration_days']) ? (int)$data['duration_days'] : 90;
        $mentorId = !empty($data['mentor_id']) ? (int)$data['mentor_id'] : null;

        $stmt = $conn->prepare("UPDATE students SET student_code = ?, name = ?, major = ?, university = ?, faculty = ?, phone = ?, start_date = ?, duration_days = ?, mentor_id = ? WHERE id = ?");
        $stmt->bind_param("sssssssiii", $data['student_code'], $data['name'], $data['major'], $data['university'], $data['faculty'], $data['phone'], $startDate, $duration, $mentorId, $studentId);
        $stmt->execute();
        $stmt->close();

        writeAuditLog(
            $_SESSION['user']['id'],
            $_SESSION['user']['username'],
            $_SESSION['user']['role'],
            'UPDATE',
            'students',
            $studentId,
            $oldStudent,
            ['student_code' => $data['student_code'], 'name' => $data['name'], 'major' => $data['major'], 'mentor_id' => $mentorId],
            "แก้ไขข้อมูลนักศึกษา: {$data['name']} (รหัส: {$data['student_code']})"
        );

        respond(["success" => true]);
    }

    if ($action === 'get_student_inspect') {
        checkRole(['admin']);
        $studentId = intval($_GET['student_id'] ?? 0);
        if ($studentId <= 0) {
            respond(["success" => false, "error" => "ระบุรหัสนักศึกษาไม่ถูกต้อง"], 400);
        }

        $sStmt = $conn->prepare("SELECT s.*, m.name AS mentor_name, m.department AS mentor_department, m.email AS mentor_email 
                                 FROM students s 
                                 LEFT JOIN mentors m ON s.mentor_id = m.id 
                                 WHERE s.id = ? AND (s.is_deleted = 0 OR s.is_deleted IS NULL)");
        $sStmt->bind_param("i", $studentId);
        $sStmt->execute();
        $student = $sStmt->get_result()->fetch_assoc();
        $sStmt->close();

        if (!$student) {
            respond(["success" => false, "error" => "ไม่พบข้อมูลนักศึกษา"], 404);
        }

        $lStmt = $conn->prepare("SELECT * FROM internship_logs WHERE student_id = ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY log_date DESC, id DESC");
        $lStmt->bind_param("i", $studentId);
        $lStmt->execute();
        $logs = $lStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $lStmt->close();

        $eStmt = $conn->prepare("SELECT * FROM evaluations WHERE student_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
        $eStmt->bind_param("i", $studentId);
        $eStmt->execute();
        $evaluation = $eStmt->get_result()->fetch_assoc();
        $eStmt->close();

        respond([
            "success" => true,
            "student" => $student,
            "logs" => $logs,
            "evaluation" => $evaluation
        ]);
    }

    // ---------------------------------------------------------
    // 4) LOGS MANAGEMENT
    // ---------------------------------------------------------
    if ($action === 'get_logs') {
        checkRole(['admin', 'mentor', 'student']);
        $user = $_SESSION['user'];

        $search = isset($_GET['search']) && $_GET['search'] !== '' ? '%' . trim($_GET['search']) . '%' : '%';
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : '%';

        if ($user['role'] === 'student') {
            $stmt = $conn->prepare("
                SELECT internship_logs.*, students.name AS student_name 
                FROM internship_logs 
                JOIN students ON internship_logs.student_id = students.id 
                WHERE internship_logs.student_id = ? 
                  AND (internship_logs.is_deleted = 0 OR internship_logs.is_deleted IS NULL)
                  AND (students.name LIKE ? OR internship_logs.work_description LIKE ?) 
                  AND internship_logs.status LIKE ? 
                ORDER BY internship_logs.log_date DESC
            ");
            $stmt->bind_param("isss", $user['ref_id'], $search, $search, $status);
        } else if ($user['role'] === 'mentor') {
            $stmt = $conn->prepare("
                SELECT internship_logs.*, students.name AS student_name 
                FROM internship_logs 
                JOIN students ON internship_logs.student_id = students.id 
                WHERE students.mentor_id = ? 
                  AND (internship_logs.is_deleted = 0 OR internship_logs.is_deleted IS NULL)
                  AND (students.name LIKE ? OR internship_logs.work_description LIKE ?) 
                  AND internship_logs.status LIKE ? 
                ORDER BY internship_logs.log_date DESC
            ");
            $stmt->bind_param("isss", $user['ref_id'], $search, $search, $status);
        } else {
            $stmt = $conn->prepare("
                SELECT internship_logs.*, students.name AS student_name 
                FROM internship_logs 
                JOIN students ON internship_logs.student_id = students.id 
                WHERE (internship_logs.is_deleted = 0 OR internship_logs.is_deleted IS NULL)
                  AND (students.name LIKE ? OR internship_logs.work_description LIKE ?) 
                  AND internship_logs.status LIKE ? 
                ORDER BY internship_logs.log_date DESC
            ");
            $stmt->bind_param("sss", $search, $search, $status);
        }

        $stmt->execute();
        respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if ($action === 'add_log') {
        checkRole(['admin', 'mentor', 'student']);
        $data = getJsonBody();
        $user = $_SESSION['user'];

        $student_id = ($user['role'] === 'student') ? $user['ref_id'] : $data['student_id'];
        $status = 'pending';

        $stmt = $conn->prepare("INSERT INTO internship_logs (student_id, log_date, work_description, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $student_id, $data['log_date'], $data['work_description'], $status);
        $stmt->execute();
        $log_id = $stmt->insert_id;
        $stmt->close();

        writeAuditLog(
            $user['id'],
            $user['username'],
            $user['role'],
            'CREATE',
            'internship_logs',
            $log_id,
            null,
            ['student_id' => $student_id, 'log_date' => $data['log_date'], 'work_description' => $data['work_description'], 'status' => $status],
            "นักศึกษาบันทึกงานประจำวัน วันที่ {$data['log_date']}"
        );

        respond(["success" => true, "id" => $log_id]);
    }

    if ($action === 'update_log') {
        checkRole(['student', 'admin']);
        $data = getJsonBody();
        $user = $_SESSION['user'];
        $log_id = (int)($data['id'] ?? 0);
        $work_description = trim($data['work_description'] ?? '');

        if ($log_id <= 0 || empty($work_description)) {
            respond(["success" => false, "error" => "ข้อมูลไม่ถูกต้องหรือไม่ได้กรอกข้อความ"], 400);
        }

        $oldLog = getRecordBeforeAction('internship_logs', $log_id);

        if ($user['role'] === 'student') {
            $checkStmt = $conn->prepare("SELECT student_id FROM internship_logs WHERE id = ?");
            $checkStmt->bind_param("i", $log_id);
            $checkStmt->execute();
            $log = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if (!$log || $log['student_id'] != $user['ref_id']) {
                respond(["success" => false, "error" => "คุณไม่มีสิทธิ์แก้ไขบันทึกนี้"], 403);
            }
        }

        $stmt = $conn->prepare("UPDATE internship_logs SET work_description = ? WHERE id = ?");
        $stmt->bind_param("si", $work_description, $log_id);
        $stmt->execute();
        $stmt->close();

        writeAuditLog(
            $user['id'],
            $user['username'],
            $user['role'],
            'UPDATE',
            'internship_logs',
            $log_id,
            $oldLog,
            ['work_description' => $work_description],
            "ผู้ใช้ {$user['username']} ({$user['role']}) แก้ไขข้อความบันทึกงานประจำวัน ID: {$log_id}"
        );

        respond(["success" => true, "message" => "แก้ไขคำผิดบันทึกเรียบร้อยแล้ว"]);
    }

    if ($action === 'mentor_edit_log') {
        checkRole(['mentor', 'admin']);
        $data = getJsonBody();
        $user = $_SESSION['user'];
        $log_id = (int)($data['id'] ?? 0);
        $work_description = trim($data['work_description'] ?? '');

        if ($log_id <= 0 || empty($work_description)) {
            respond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }

        $oldLog = getRecordBeforeAction('internship_logs', $log_id);

        $stmt = $conn->prepare("UPDATE internship_logs SET work_description = ? WHERE id = ?");
        $stmt->bind_param("si", $work_description, $log_id);
        $stmt->execute();
        $stmt->close();

        writeAuditLog(
            $user['id'],
            $user['username'],
            $user['role'],
            'UPDATE',
            'internship_logs',
            $log_id,
            $oldLog,
            ['work_description' => $work_description],
            "พี่เลี้ยง ({$user['username']}) แก้ไขข้อความบันทึกงานของนักศึกษา ID: {$log_id}"
        );

        respond(["success" => true, "message" => "พี่เลี้ยงแก้ไขคำผิดเรียบร้อยแล้ว"]);
    }

    if ($action === 'update_mentor_comment') {
        checkRole(['admin', 'mentor']);
        $data = getJsonBody();
        $user = $_SESSION['user'];
        $log_id = (int)($data['id'] ?? $data['log_id'] ?? 0);
        $mentor_comment = trim($data['mentor_comment'] ?? '');

        if ($log_id <= 0) {
            respond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }

        $oldLog = getRecordBeforeAction('internship_logs', $log_id);

        $stmt = $conn->prepare("UPDATE internship_logs SET mentor_comment = ? WHERE id = ?");
        $stmt->bind_param("si", $mentor_comment, $log_id);
        $stmt->execute();
        $stmt->close();

        writeAuditLog(
            $user['id'],
            $user['username'],
            $user['role'],
            'UPDATE',
            'internship_logs',
            $log_id,
            $oldLog,
            ['mentor_comment' => $mentor_comment],
            "พี่เลี้ยง ({$user['username']}) แก้ไขข้อเสนอแนะในบันทึกงาน ID: {$log_id}"
        );

        respond(["success" => true, "message" => "แก้ไขข้อความเสร็จสิ้น"]);
    }

    if ($action === 'approve_log') {
        checkRole(['admin', 'mentor']);
        $data = getJsonBody();
        $log_id = (int)($data['log_id'] ?? 0);
        $comment = $data['mentor_comment'] ?? null;
        $newStatus = $data['status'] ?? 'approved';

        $oldLog = getRecordBeforeAction('internship_logs', $log_id);

        $stmt = $conn->prepare("UPDATE internship_logs SET status = ?, mentor_comment = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $comment, $log_id);
        $stmt->execute();
        $stmt->close();

        $statusTh = ($newStatus === 'approved') ? 'อนุมัติ' : (($newStatus === 'revision') ? 'ส่งกลับให้แก้ไข' : 'ปฏิเสธ/ไม่อนุมัติ');
        writeAuditLog(
            $_SESSION['user']['id'],
            $_SESSION['user']['username'],
            $_SESSION['user']['role'],
            'UPDATE',
            'internship_logs',
            $log_id,
            $oldLog,
            ['status' => $newStatus, 'mentor_comment' => $comment],
            "พี่เลี้ยงเปลี่ยนสถานะบันทึกงานเป็น [{$statusTh}] (ID: {$log_id})"
        );

        respond(["success" => true]);
    }

    // ---------------------------------------------------------
    // 5) EVALUATIONS
    // ---------------------------------------------------------
    if ($action === 'save_evaluation') {
        checkRole(['admin', 'mentor']);
        $data = getJsonBody();
        $user = $_SESSION['user'];
        $student_id = (int)($data['student_id'] ?? 0);
        $mentor_id = ($user['role'] === 'mentor') ? $user['ref_id'] : ($data['mentor_id'] ?? null);

        $checkStmt = $conn->prepare("SELECT id FROM evaluations WHERE student_id = ?");
        $checkStmt->bind_param("i", $student_id);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existing) {
            $evalId = (int)$existing['id'];
            $oldEval = getRecordBeforeAction('evaluations', $evalId);

            $stmt = $conn->prepare("UPDATE evaluations SET mentor_id = ?, score_work = ?, score_time = ?, score_behavior = ?, final_feedback = ?, evaluated_at = CURRENT_TIMESTAMP WHERE student_id = ?");
            $stmt->bind_param("iiiisi", $mentor_id, $data['score_work'], $data['score_time'], $data['score_behavior'], $data['final_feedback'], $student_id);
            $stmt->execute();
            $stmt->close();

            writeAuditLog(
                $user['id'],
                $user['username'],
                $user['role'],
                'UPDATE',
                'evaluations',
                $evalId,
                $oldEval,
                ['score_work' => $data['score_work'], 'score_time' => $data['score_time'], 'score_behavior' => $data['score_behavior'], 'final_feedback' => $data['final_feedback']],
                "อัปเดตผลการประเมินนักศึกษา (Student ID: {$student_id})"
            );

            respond(["success" => true, "message" => "อัปเดตการประเมินผลเรียบร้อยแล้ว"]);
        } else {
            $stmt = $conn->prepare("INSERT INTO evaluations (student_id, mentor_id, score_work, score_time, score_behavior, final_feedback) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiis", $student_id, $mentor_id, $data['score_work'], $data['score_time'], $data['score_behavior'], $data['final_feedback']);
            $stmt->execute();
            $newEvalId = $stmt->insert_id;
            $stmt->close();

            writeAuditLog(
                $user['id'],
                $user['username'],
                $user['role'],
                'CREATE',
                'evaluations',
                $newEvalId,
                null,
                ['student_id' => $student_id, 'mentor_id' => $mentor_id, 'score_work' => $data['score_work'], 'score_time' => $data['score_time'], 'score_behavior' => $data['score_behavior'], 'final_feedback' => $data['final_feedback']],
                "บันทึกการประเมินผลนักศึกษาใหม่ (Student ID: {$student_id})"
            );

            respond(["success" => true, "id" => $newEvalId, "message" => "บันทึกการประเมินผลสำเร็จ"]);
        }
    }

    if ($action === 'get_evaluation') {
        checkRole(['admin', 'mentor', 'student']);
        $student_id = (int)($_GET['student_id'] ?? 0);

        $stmt = $conn->prepare("SELECT * FROM evaluations WHERE student_id = ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY evaluated_at DESC LIMIT 1");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        respond($stmt->get_result()->fetch_assoc());
    }

    // ---------------------------------------------------------
    // 6) DELETE ACTION (Soft Delete Integration with Audit Log)
    // ---------------------------------------------------------
    if ($action === 'delete') {
        $type = $_GET['type'] ?? '';
        $id = (int)($_GET['id'] ?? 0);
        $user = $_SESSION['user'];

        if ($type === 'logs') $type = 'internship_logs';

        $allowedTypes = ['students', 'mentors', 'internship_logs', 'evaluations'];
        if (!in_array($type, $allowedTypes, true) || $id <= 0) {
            respond(["success" => false, "error" => "พารามิเตอร์ไม่ถูกต้อง"], 400);
        }

        if ($type === 'mentors' || $type === 'students') {
            checkRole(['admin']);
        } else if ($type === 'internship_logs') {
            checkRole(['admin', 'student']);
            if ($user['role'] === 'student') {
                $checkStmt = $conn->prepare("SELECT student_id FROM internship_logs WHERE id = ?");
                $checkStmt->bind_param("i", $id);
                $checkStmt->execute();
                $logOwner = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                if (!$logOwner || $logOwner['student_id'] != $user['ref_id']) {
                    respond(["success" => false, "error" => "คุณไม่มีสิทธิ์ลบบันทึกนี้"], 403);
                }
            }
        }

        $res = softDelete($type, $id, $user['id'], $user['username'], $user['role']);
        if ($res['success']) {
            respond(["success" => true, "message" => $res['message']]);
        } else {
            respond(["success" => false, "error" => $res['error'] ?? 'เกิดข้อผิดพลาดในการลบข้อมูล'], 400);
        }
    }

    // ---------------------------------------------------------
    // 7) DASHBOARD & REAL-TIME ANALYTICS (Admin Only)
    // ---------------------------------------------------------
    if ($action === 'dashboard_analytics' || $action === 'dashboard_stats') {
        checkRole(['admin']);

        // 1. ดึงข้อมูลนักศึกษาทั้งหมดพร้อมพี่เลี้ยงและการประเมิน (ไม่รวมที่ถูกลบ)
        $stdQuery = "
            SELECT 
                s.*, 
                m.name AS mentor_name, 
                m.department AS mentor_department,
                m.email AS mentor_email,
                e.score_work,
                e.score_time,
                e.score_behavior,
                e.final_feedback,
                (SELECT COUNT(*) FROM internship_logs l WHERE l.student_id = s.id AND (l.is_deleted = 0 OR l.is_deleted IS NULL)) AS total_logs,
                (SELECT COUNT(*) FROM internship_logs l WHERE l.student_id = s.id AND l.status = 'approved' AND (l.is_deleted = 0 OR l.is_deleted IS NULL)) AS approved_logs,
                (SELECT COUNT(*) FROM internship_logs l WHERE l.student_id = s.id AND l.status = 'pending' AND (l.is_deleted = 0 OR l.is_deleted IS NULL)) AS pending_logs,
                (SELECT COUNT(*) FROM internship_logs l WHERE l.student_id = s.id AND l.status = 'revision' AND (l.is_deleted = 0 OR l.is_deleted IS NULL)) AS revision_logs,
                (SELECT COUNT(*) FROM internship_logs l WHERE l.student_id = s.id AND l.status = 'rejected' AND (l.is_deleted = 0 OR l.is_deleted IS NULL)) AS rejected_logs
            FROM students s
            LEFT JOIN mentors m ON s.mentor_id = m.id AND (m.is_deleted = 0 OR m.is_deleted IS NULL)
            LEFT JOIN evaluations e ON s.id = e.student_id AND (e.is_deleted = 0 OR e.is_deleted IS NULL)
            WHERE (s.is_deleted = 0 OR s.is_deleted IS NULL)
            ORDER BY s.id DESC
        ";
        $studentsRes = $conn->query($stdQuery);
        $students = $studentsRes ? $studentsRes->fetch_all(MYSQLI_ASSOC) : [];

        // 2. คำนวณสถานะความก้าวหน้านักศึกษา
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        $statusCount = [
            'active'    => 0,
            'ending'    => 0,
            'completed' => 0
        ];

        $deptStats = [];
        $uniStats  = [];

        $studentsSummary = [];

        foreach ($students as $std) {
            $startDate = !empty($std['start_date']) ? new DateTime($std['start_date']) : clone $today;
            $startDate->setTime(0, 0, 0);
            $duration = !empty($std['duration_days']) ? (int)$std['duration_days'] : 90;
            
            $expireDate = clone $startDate;
            $expireDate->modify("+{$duration} days");

            $diffDays = (int)$today->diff($startDate)->format('%r%a');
            $daysTrained = $diffDays < 0 ? 0 : ($diffDays > $duration ? $duration : $diffDays + 1);
            $daysRemaining = (int)$today->diff($expireDate)->format('%r%a');
            $percentage = $duration > 0 ? (int)round(($daysTrained / $duration) * 100) : 0;
            if ($percentage > 100) $percentage = 100;

            $statusKey = 'active';
            $statusLabel = '🔷 กำลังฝึกงาน';
            if ($daysRemaining <= 0) {
                $statusKey = 'completed';
                $statusLabel = '✅ จบการฝึกงาน';
                $statusCount['completed']++;
            } else if ($daysRemaining <= 7) {
                $statusKey = 'ending';
                $statusLabel = "⚠️ ใกล้จบ ({$daysRemaining} วัน)";
                $statusCount['ending']++;
            } else {
                $statusCount['active']++;
            }

            $dept = !empty($std['mentor_department']) ? trim($std['mentor_department']) : 'ยังไม่ระบุแผนก';
            if (!isset($deptStats[$dept])) {
                $deptStats[$dept] = [
                    'department'     => $dept,
                    'total_students' => 0,
                    'active'         => 0,
                    'ending'         => 0,
                    'completed'      => 0,
                    'total_logs'     => 0,
                    'approved_logs'  => 0,
                    'mentors'        => []
                ];
            }
            $deptStats[$dept]['total_students']++;
            $deptStats[$dept][$statusKey]++;
            $deptStats[$dept]['total_logs'] += (int)$std['total_logs'];
            $deptStats[$dept]['approved_logs'] += (int)$std['approved_logs'];
            if (!empty($std['mentor_name']) && !in_array($std['mentor_name'], $deptStats[$dept]['mentors'], true)) {
                $deptStats[$dept]['mentors'][] = $std['mentor_name'];
            }

            $uni = !empty($std['university']) ? trim($std['university']) : 'ไม่ระบุมหาวิทยาลัย';
            $uniStats[$uni] = ($uniStats[$uni] ?? 0) + 1;

            $studentsSummary[] = [
                'id'             => (int)$std['id'],
                'student_code'   => $std['student_code'],
                'name'           => $std['name'],
                'university'     => $std['university'] ?? '-',
                'faculty'        => $std['faculty'] ?? '-',
                'major'          => $std['major'] ?? '-',
                'phone'          => $std['phone'] ?? '-',
                'start_date'     => $std['start_date'],
                'duration_days'  => $duration,
                'expire_date'    => $expireDate->format('Y-m-d'),
                'days_trained'   => $daysTrained,
                'days_remaining' => $daysRemaining,
                'percentage'     => $percentage,
                'status_key'     => $statusKey,
                'status_label'   => $statusLabel,
                'mentor_name'    => $std['mentor_name'] ?? 'ยังไม่ระบุ',
                'mentor_dept'    => $dept,
                'total_logs'     => (int)$std['total_logs'],
                'approved_logs'  => (int)$std['approved_logs'],
                'pending_logs'   => (int)$std['pending_logs'],
                'revision_logs'  => (int)$std['revision_logs'],
                'rejected_logs'  => (int)$std['rejected_logs'],
                'score_work'     => $std['score_work'] !== null ? (int)$std['score_work'] : null,
                'score_time'     => $std['score_time'] !== null ? (int)$std['score_time'] : null,
                'score_behavior' => $std['score_behavior'] !== null ? (int)$std['score_behavior'] : null,
                'final_feedback' => $std['final_feedback'] ?? null
            ];
        }

        // เรียงมหาวิทยาลัยตามจำนวน
        arsort($uniStats);
        $topUniversities = [];
        foreach (array_slice($uniStats, 0, 8, true) as $uName => $uCount) {
            $topUniversities[] = ['university' => $uName, 'count' => $uCount];
        }

        // 3. ดึงข้อมูลสถิติพี่เลี้ยง (ไม่รวมที่ถูกลบ)
        $mentorsQuery = "
            SELECT 
                m.*,
                (SELECT COUNT(*) FROM students s WHERE s.mentor_id = m.id AND (s.is_deleted = 0 OR s.is_deleted IS NULL)) AS student_count,
                (SELECT COUNT(*) FROM internship_logs l JOIN students s ON l.student_id = s.id WHERE s.mentor_id = m.id AND (l.is_deleted = 0 OR l.is_deleted IS NULL) AND (s.is_deleted = 0 OR s.is_deleted IS NULL)) AS total_logs,
                (SELECT COUNT(*) FROM internship_logs l JOIN students s ON l.student_id = s.id WHERE s.mentor_id = m.id AND l.status = 'approved' AND (l.is_deleted = 0 OR l.is_deleted IS NULL) AND (s.is_deleted = 0 OR s.is_deleted IS NULL)) AS approved_logs,
                (SELECT COUNT(*) FROM internship_logs l JOIN students s ON l.student_id = s.id WHERE s.mentor_id = m.id AND l.status = 'pending' AND (l.is_deleted = 0 OR l.is_deleted IS NULL) AND (s.is_deleted = 0 OR s.is_deleted IS NULL)) AS pending_logs,
                (SELECT AVG((e.score_work + e.score_time + e.score_behavior) / 3.0) FROM evaluations e JOIN students s ON e.student_id = s.id WHERE s.mentor_id = m.id AND (e.is_deleted = 0 OR e.is_deleted IS NULL) AND (s.is_deleted = 0 OR s.is_deleted IS NULL)) AS avg_score
            FROM mentors m
            WHERE (m.is_deleted = 0 OR m.is_deleted IS NULL)
            ORDER BY student_count DESC, m.id DESC
        ";
        $mentorsRes = $conn->query($mentorsQuery);
        $mentorsList = [];
        if ($mentorsRes) {
            while ($m = $mentorsRes->fetch_assoc()) {
                $mentorsList[] = [
                    'id'            => (int)$m['id'],
                    'name'          => $m['name'],
                    'email'         => $m['email'],
                    'department'    => $m['department'] ?: 'ยังไม่ระบุ',
                    'student_count' => (int)$m['student_count'],
                    'total_logs'    => (int)$m['total_logs'],
                    'approved_logs' => (int)$m['approved_logs'],
                    'pending_logs'  => (int)$m['pending_logs'],
                    'avg_score'     => $m['avg_score'] !== null ? round((float)$m['avg_score'], 1) : null
                ];
            }
        }

        // 4. สรุปภาพรวมบันทึกงานทั้งระบบ (ไม่รวมที่ถูกลบ)
        $logsSummaryQuery = "
            SELECT 
                COUNT(*) AS total_logs,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_logs,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_logs,
                SUM(CASE WHEN status = 'revision' THEN 1 ELSE 0 END) AS revision_logs,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_logs
            FROM internship_logs
            WHERE (is_deleted = 0 OR is_deleted IS NULL)
        ";
        $logsRes = $conn->query($logsSummaryQuery);
        $logsStats = $logsRes ? $logsRes->fetch_assoc() : [
            'total_logs'    => 0,
            'approved_logs' => 0,
            'pending_logs'  => 0,
            'revision_logs' => 0,
            'rejected_logs' => 0
        ];

        $totalLogs = (int)($logsStats['total_logs'] ?? 0);
        $approvedLogs = (int)($logsStats['approved_logs'] ?? 0);
        $approvalRate = $totalLogs > 0 ? round(($approvedLogs / $totalLogs) * 100, 1) : 0;

        // จัดรูปแบบ Departments เป็น Array สำหรับ Chart/Table
        $departmentsArray = array_values($deptStats);
        usort($departmentsArray, function($a, $b) {
            return $b['total_students'] - $a['total_students'];
        });

        respond([
            "success"   => true,
            "timestamp" => date('Y-m-d H:i:s'),
            "kpis" => [
                "total_students"     => count($students),
                "active_students"    => $statusCount['active'] + $statusCount['ending'],
                "ending_students"    => $statusCount['ending'],
                "completed_students" => $statusCount['completed'],
                "total_mentors"      => count($mentorsList),
                "total_departments"  => count($deptStats),
                "total_logs"         => $totalLogs,
                "approved_logs"      => $approvedLogs,
                "pending_logs"       => (int)($logsStats['pending_logs'] ?? 0),
                "need_action_logs"   => (int)($logsStats['revision_logs'] ?? 0) + (int)($logsStats['rejected_logs'] ?? 0),
                "approval_rate"      => $approvalRate
            ],
            "status_distribution" => [
                ["label" => "🔷 กำลังฝึกงาน", "key" => "active", "count" => $statusCount['active'], "color" => "#2563eb"],
                ["label" => "⚠️ ใกล้จบ (<= 7 วัน)", "key" => "ending", "count" => $statusCount['ending'], "color" => "#f59e0b"],
                ["label" => "✅ จบการฝึกงานแล้ว", "key" => "completed", "count" => $statusCount['completed'], "color" => "#10b981"]
            ],
            "logs_status" => [
                ["label" => "อนุมัติแล้ว", "count" => $approvedLogs, "color" => "#10b981"],
                ["label" => "รออนุมัติ", "count" => (int)($logsStats['pending_logs'] ?? 0), "color" => "#f59e0b"],
                ["label" => "ส่งกลับแก้ไข", "count" => (int)($logsStats['revision_logs'] ?? 0), "color" => "#0ea5e9"],
                ["label" => "ปฏิเสธ", "count" => (int)($logsStats['rejected_logs'] ?? 0), "color" => "#ef4444"]
            ],
            "departments"      => $departmentsArray,
            "top_universities" => $topUniversities,
            "mentors_summary"  => $mentorsList,
            "students_summary" => $studentsSummary
        ]);
    }

    // ---------------------------------------------------------
    // 8) BATCH IMPORT STUDENTS (Admin Only - High Capacity)
    // ---------------------------------------------------------
    if ($action === 'batch_import_students') {
        checkRole(['admin']);
        $data = getJsonBody();
        $studentsList = $data['students'] ?? [];

        if (!is_array($studentsList) || count($studentsList) === 0) {
            respond(["success" => false, "error" => "ไม่พบข้อมูลนักศึกษาที่ต้องการนำเข้า"], 400);
        }

        // ดึง Mentor ทั้งหมดมาทำ Map (ทั้ง id, email, name)
        $mRes = $conn->query("SELECT id, name, email FROM mentors WHERE (is_deleted = 0 OR is_deleted IS NULL)");
        $mentorMapById = [];
        $mentorMapByEmail = [];
        $mentorMapByName = [];
        if ($mRes) {
            while ($m = $mRes->fetch_assoc()) {
                $mentorMapById[(int)$m['id']] = (int)$m['id'];
                $mentorMapByEmail[strtolower(trim($m['email']))] = (int)$m['id'];
                $mentorMapByName[mb_strtolower(trim($m['name']), 'UTF-8')] = (int)$m['id'];
            }
        }

        // ดึง Users และ Students เดิมมาตรวจสอบความซ้ำซ้อน
        $existingCodes = [];
        $eRes = $conn->query("SELECT student_code FROM students");
        if ($eRes) {
            while ($row = $eRes->fetch_assoc()) {
                $existingCodes[strtolower(trim($row['student_code']))] = true;
            }
        }

        $existingUsernames = [];
        $uRes = $conn->query("SELECT username FROM users");
        if ($uRes) {
            while ($row = $uRes->fetch_assoc()) {
                $existingUsernames[strtolower(trim($row['username']))] = true;
            }
        }

        $importedCount = 0;
        $updatedCount  = 0;
        $skippedCount  = 0;
        $errors        = [];

        $defaultPassword = password_hash("123456", PASSWORD_BCRYPT);

        // ใช้ Transaction เพื่อความรวดเร็วและปลอดภัยในระดับหลายพันแถว
        $conn->begin_transaction();

        $insertStudentStmt = $conn->prepare("
            INSERT INTO students (student_code, name, major, university, faculty, phone, start_date, duration_days, mentor_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $updateStudentStmt = $conn->prepare("
            UPDATE students 
            SET name = ?, major = ?, university = ?, faculty = ?, phone = ?, start_date = ?, duration_days = ?, mentor_id = ?, is_deleted = 0 
            WHERE student_code = ?
        ");

        $insertUserStmt = $conn->prepare("
            INSERT INTO users (username, password, role, ref_id) 
            VALUES (?, ?, 'student', ?)
        ");

        $updateUserStmt = $conn->prepare("
            UPDATE users SET ref_id = ? WHERE username = ? AND role = 'student'
        ");

        foreach ($studentsList as $index => $row) {
            $rowNum = $index + 1;
            $code = trim($row['student_code'] ?? $row['code'] ?? $row['Username'] ?? '');
            $name = trim($row['name'] ?? $row['fullname'] ?? '');
            $uni  = trim($row['university'] ?? '');
            $faculty = trim($row['faculty'] ?? '');
            $major = trim($row['major'] ?? '');
            $phone = trim($row['phone'] ?? '');
            $startDate = trim($row['start_date'] ?? $row['startDate'] ?? date('Y-m-d'));
            $duration = !empty($row['duration_days']) ? (int)$row['duration_days'] : (!empty($row['duration']) ? (int)$row['duration'] : 90);
            
            // ตรวจสอบวันที่
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                $startDate = date('Y-m-d');
            }

            if (empty($code) || empty($name)) {
                $skippedCount++;
                $errors[] = "แถวที่ {$rowNum}: ไม่มีรหัสนักศึกษาหรือชื่อ-นามสกุล";
                continue;
            }

            // หา Mentor ID
            $mentorRef = trim((string)($row['mentor_id'] ?? $row['mentor'] ?? $row['mentor_email'] ?? $row['mentor_name'] ?? ''));
            $mentorId = null;
            if (!empty($mentorRef)) {
                if (is_numeric($mentorRef) && isset($mentorMapById[(int)$mentorRef])) {
                    $mentorId = (int)$mentorRef;
                } else if (isset($mentorMapByEmail[strtolower($mentorRef)])) {
                    $mentorId = $mentorMapByEmail[strtolower($mentorRef)];
                } else if (isset($mentorMapByName[mb_strtolower($mentorRef, 'UTF-8')])) {
                    $mentorId = $mentorMapByName[mb_strtolower($mentorRef, 'UTF-8')];
                }
            }

            $lowerCode = strtolower($code);

            if (isset($existingCodes[$lowerCode])) {
                // อัปเดตข้อมูลนักศึกษาที่มีอยู่เดิม
                $updateStudentStmt->bind_param("ssssssiis", $name, $major, $uni, $faculty, $phone, $startDate, $duration, $mentorId, $code);
                $updateStudentStmt->execute();

                // หา student id เพื่อ sync ref_id ใน users
                $sFind = $conn->prepare("SELECT id FROM students WHERE student_code = ?");
                $sFind->bind_param("s", $code);
                $sFind->execute();
                $sRow = $sFind->get_result()->fetch_assoc();
                $sFind->close();

                if ($sRow) {
                    $sId = $sRow['id'];
                    if (isset($existingUsernames[$lowerCode])) {
                        $updateUserStmt->bind_param("is", $sId, $code);
                        $updateUserStmt->execute();
                    } else {
                        $insertUserStmt->bind_param("ssi", $code, $defaultPassword, $sId);
                        $insertUserStmt->execute();
                        $existingUsernames[$lowerCode] = true;
                    }
                }

                $updatedCount++;
            } else {
                // เพิ่มนักศึกษาใหม่
                $insertStudentStmt->bind_param("sssssssii", $code, $name, $major, $uni, $faculty, $phone, $startDate, $duration, $mentorId);
                $insertStudentStmt->execute();
                $studentId = $insertStudentStmt->insert_id;

                if ($studentId) {
                    $existingCodes[$lowerCode] = true;

                    // สร้างหรือผูก User Account
                    if (isset($existingUsernames[$lowerCode])) {
                        $updateUserStmt->bind_param("is", $studentId, $code);
                        $updateUserStmt->execute();
                    } else {
                        $insertUserStmt->bind_param("ssi", $code, $defaultPassword, $studentId);
                        $insertUserStmt->execute();
                        $existingUsernames[$lowerCode] = true;
                    }
                    $importedCount++;
                } else {
                    $skippedCount++;
                    $errors[] = "แถวที่ {$rowNum}: ไม่สามารถบันทึกนักศึกษา {$code} ได้";
                }
            }
        }

        $insertStudentStmt->close();
        $updateStudentStmt->close();
        $insertUserStmt->close();
        $updateUserStmt->close();

        $conn->commit();

        writeAuditLog(
            $_SESSION['user']['id'],
            $_SESSION['user']['username'],
            $_SESSION['user']['role'],
            'CREATE',
            'students',
            0,
            null,
            ['imported' => $importedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount],
            "นำเข้าข้อมูลนักศึกษาผ่านไฟล์ CSV: เพิ่มใหม่ {$importedCount} คน, อัปเดต {$updatedCount} คน"
        );

        respond([
            "success"  => true,
            "message"  => "นำเข้าข้อมูลเสร็จสิ้น: เพิ่มใหม่ {$importedCount} คน, อัปเดต {$updatedCount} คน, ข้าม {$skippedCount} คน",
            "imported" => $importedCount,
            "updated"  => $updatedCount,
            "skipped"  => $skippedCount,
            "errors"   => $errors
        ]);
    }

    // ---------------------------------------------------------
    // 9) EXPORT CSV ACTIONS (Admin Only)
    // ---------------------------------------------------------
    if ($action === 'export_students_csv' || $action === 'export_students') {
        checkRole(['admin']);
        
        $sql = "
            SELECT 
                s.student_code, 
                s.name, 
                s.university, 
                s.faculty, 
                s.major, 
                s.phone, 
                s.start_date, 
                s.duration_days, 
                m.name AS mentor_name, 
                m.department AS mentor_department,
                e.score_work,
                e.score_time,
                e.score_behavior
            FROM students s 
            LEFT JOIN mentors m ON s.mentor_id = m.id AND (m.is_deleted = 0 OR m.is_deleted IS NULL)
            LEFT JOIN evaluations e ON s.id = e.student_id AND (e.is_deleted = 0 OR e.is_deleted IS NULL)
            WHERE (s.is_deleted = 0 OR s.is_deleted IS NULL)
            ORDER BY s.id DESC
        ";
        $result = $conn->query($sql);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // UTF-8 BOM สำหรับ Excel ภาษาไทย
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="students_roster_'.date('Ymd_His').'.csv"');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'รหัสนักศึกษา', 
            'ชื่อ-นามสกุล', 
            'มหาวิทยาลัย', 
            'คณะ', 
            'สาขาวิชา', 
            'เบอร์โทรศัพท์', 
            'วันที่เริ่มฝึกงาน', 
            'จำนวนวันฝึก', 
            'พี่เลี้ยงควบคุม', 
            'แผนกงาน',
            'คะแนนงาน (10)',
            'คะแนนเวลา (10)',
            'คะแนนพฤติกรรม (10)'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['student_code'],
                $r['name'],
                $r['university'] ?? '-',
                $r['faculty'] ?? '-',
                $r['major'] ?? '-',
                $r['phone'] ?? '-',
                $r['start_date'],
                $r['duration_days'],
                $r['mentor_name'] ?? 'ยังไม่ระบุ',
                $r['mentor_department'] ?? 'ยังไม่ระบุ',
                $r['score_work'] !== null ? $r['score_work'] : '-',
                $r['score_time'] !== null ? $r['score_time'] : '-',
                $r['score_behavior'] !== null ? $r['score_behavior'] : '-'
            ]);
        }
        fclose($out);
        exit;
    }

    if ($action === 'export_summary_csv') {
        checkRole(['admin']);

        // ส่งออกสรุปสถิติรายแผนก
        $sql = "
            SELECT 
                COALESCE(m.department, 'ยังไม่ระบุแผนก') AS department,
                COUNT(s.id) AS student_count,
                COUNT(DISTINCT m.id) AS mentor_count,
                SUM(CASE WHEN (DATEDIFF(DATE_ADD(s.start_date, INTERVAL s.duration_days DAY), CURDATE()) > 0) THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN (DATEDIFF(DATE_ADD(s.start_date, INTERVAL s.duration_days DAY), CURDATE()) <= 0) THEN 1 ELSE 0 END) AS completed_count
            FROM students s
            LEFT JOIN mentors m ON s.mentor_id = m.id AND (m.is_deleted = 0 OR m.is_deleted IS NULL)
            WHERE (s.is_deleted = 0 OR s.is_deleted IS NULL)
            GROUP BY COALESCE(m.department, 'ยังไม่ระบุแผนก')
            ORDER BY student_count DESC
        ";
        $result = $conn->query($sql);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="department_summary_'.date('Ymd_His').'.csv"');
        echo "\xEF\xBB\xBF";
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['แผนกงาน', 'จำนวนนักศึกษาทั้งหมด', 'กำลังฝึกงานอยู่', 'จบการฝึกงานแล้ว', 'จำนวนพี่เลี้ยงในแผนก']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['department'],
                $r['student_count'],
                $r['active_count'],
                $r['completed_count'],
                $r['mentor_count']
            ]);
        }
        fclose($out);
        exit;
    }

} catch (Throwable $e) {
    respond(["success" => false, "error" => $e->getMessage()], 500);
}
?>