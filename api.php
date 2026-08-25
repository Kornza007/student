<?php
// api.php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

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
            $fullUserData = fetchUserDataWithDisplayName($conn, $user['id']);
            $_SESSION['user'] = $fullUserData;
            respond(["success" => true, "user" => $_SESSION['user']]);
        } else {
            respond(["success" => false, "error" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"], 401);
        }
    }

    if ($action === 'logout') {
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

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE role = ? AND ref_id = ?");
        $stmt->bind_param("ssi", $hashedPassword, $targetRole, $targetRefId);

        if ($stmt->execute()) {
            $stmt->close();
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
            $stmt = $conn->prepare("SELECT * FROM mentors WHERE id = ?");
            $stmt->bind_param("i", $user['ref_id']);
            $stmt->execute();
            respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            $result = $conn->query("SELECT * FROM mentors ORDER BY id DESC");
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

        respond(["success" => true, "id" => $mentor_id, "message" => "สร้างข้อมูลพี่เลี้ยงและสร้างบัญชีล็อกอินสำเร็จ"]);
    }

    if ($action === 'update_mentor') {
        checkRole(['admin']);
        $data = getJsonBody();
        $stmt = $conn->prepare("UPDATE mentors SET name = ?, email = ?, department = ? WHERE id = ?");
        $stmt->bind_param("sssi", $data['name'], $data['email'], $data['department'], $data['id']);
        $stmt->execute();
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
            $stmt = $conn->prepare("SELECT students.*, mentors.name AS mentor_name FROM students LEFT JOIN mentors ON students.mentor_id = mentors.id WHERE students.mentor_id = ?");
            $stmt->bind_param("i", $user['ref_id']);
            $stmt->execute();
            respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else if ($user['role'] === 'student') {
            $stmt = $conn->prepare("SELECT students.*, mentors.name AS mentor_name FROM students LEFT JOIN mentors ON students.mentor_id = mentors.id WHERE students.id = ?");
            $stmt->bind_param("i", $user['ref_id']);
            $stmt->execute();
            respond($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            $result = $conn->query("SELECT students.*, mentors.name AS mentor_name FROM students LEFT JOIN mentors ON students.mentor_id = mentors.id ORDER BY students.id DESC");
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

        respond(["success" => true, "id" => $student_id, "message" => "สร้างข้อมูลนักศึกษาและสร้างบัญชีล็อกอินสำเร็จ"]);
    }

    if ($action === 'update_student') {
        checkRole(['admin']);
        $data = getJsonBody();
        $startDate = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
        $duration = !empty($data['duration_days']) ? (int)$data['duration_days'] : 90;
        $mentorId = !empty($data['mentor_id']) ? (int)$data['mentor_id'] : null;

        $stmt = $conn->prepare("UPDATE students SET student_code = ?, name = ?, major = ?, university = ?, faculty = ?, phone = ?, start_date = ?, duration_days = ?, mentor_id = ? WHERE id = ?");
        $stmt->bind_param("sssssssiii", $data['student_code'], $data['name'], $data['major'], $data['university'], $data['faculty'], $data['phone'], $startDate, $duration, $mentorId, $data['id']);
        $stmt->execute();
        respond(["success" => true]);
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
                WHERE (students.name LIKE ? OR internship_logs.work_description LIKE ?) 
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
        respond(["success" => true, "id" => $stmt->insert_id]);
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

        if ($user['role'] === 'student') {
            $checkStmt = $conn->prepare("SELECT student_id FROM internship_logs WHERE id = ?");
            $checkStmt->bind_param("i", $log_id);
            $checkStmt->execute();
            $log = $checkStmt->get_result()->fetch_assoc();

            if (!$log || $log['student_id'] != $user['ref_id']) {
                respond(["success" => false, "error" => "คุณไม่มีสิทธิ์แก้ไขบันทึกนี้"], 403);
            }
        }

        $stmt = $conn->prepare("UPDATE internship_logs SET work_description = ? WHERE id = ?");
        $stmt->bind_param("si", $work_description, $log_id);
        $stmt->execute();
        respond(["success" => true, "message" => "แก้ไขคำผิดบันทึกเรียบร้อยแล้ว"]);
    }

    if ($action === 'mentor_edit_log') {
        checkRole(['mentor', 'admin']);
        $data = getJsonBody();
        $log_id = (int)($data['id'] ?? 0);
        $work_description = trim($data['work_description'] ?? '');

        if ($log_id <= 0 || empty($work_description)) {
            respond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }

        $stmt = $conn->prepare("UPDATE internship_logs SET work_description = ? WHERE id = ?");
        $stmt->bind_param("si", $work_description, $log_id);
        $stmt->execute();
        respond(["success" => true, "message" => "พี่เลี้ยงแก้ไขคำผิดเรียบร้อยแล้ว"]);
    }

    if ($action === 'update_mentor_comment') {
        checkRole(['admin', 'mentor']);
        $data = getJsonBody();
        $log_id = (int)($data['id'] ?? $data['log_id'] ?? 0);
        $mentor_comment = trim($data['mentor_comment'] ?? '');

        if ($log_id <= 0) {
            respond(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"], 400);
        }

        $stmt = $conn->prepare("UPDATE internship_logs SET mentor_comment = ? WHERE id = ?");
        $stmt->bind_param("si", $mentor_comment, $log_id);
        $stmt->execute();
        respond(["success" => true, "message" => "แก้ไขข้อความเสร็จสิ้น"]);
    }

    if ($action === 'approve_log') {
        checkRole(['admin', 'mentor']);
        $data = getJsonBody();
        $comment = $data['mentor_comment'] ?? null;

        $stmt = $conn->prepare("UPDATE internship_logs SET status = ?, mentor_comment = ? WHERE id = ?");
        $stmt->bind_param("ssi", $data['status'], $comment, $data['log_id']);
        $stmt->execute();
        respond(["success" => true]);
    }

    // ---------------------------------------------------------
    // 5) EVALUATIONS
    // ---------------------------------------------------------
    if ($action === 'save_evaluation') {
        checkRole(['admin', 'mentor']);
        $data = getJsonBody();
        $user = $_SESSION['user'];
        $mentor_id = ($user['role'] === 'mentor') ? $user['ref_id'] : ($data['mentor_id'] ?? null);

        $checkStmt = $conn->prepare("SELECT id FROM evaluations WHERE student_id = ?");
        $checkStmt->bind_param("i", $data['student_id']);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE evaluations SET mentor_id = ?, score_work = ?, score_time = ?, score_behavior = ?, final_feedback = ?, evaluated_at = CURRENT_TIMESTAMP WHERE student_id = ?");
            $stmt->bind_param("iiiisi", $mentor_id, $data['score_work'], $data['score_time'], $data['score_behavior'], $data['final_feedback'], $data['student_id']);
            $stmt->execute();
            respond(["success" => true, "message" => "อัปเดตการประเมินผลเรียบร้อยแล้ว"]);
        } else {
            $stmt = $conn->prepare("INSERT INTO evaluations (student_id, mentor_id, score_work, score_time, score_behavior, final_feedback) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiis", $data['student_id'], $mentor_id, $data['score_work'], $data['score_time'], $data['score_behavior'], $data['final_feedback']);
            $stmt->execute();
            respond(["success" => true, "id" => $stmt->insert_id, "message" => "บันทึกการประเมินผลสำเร็จ"]);
        }
    }

    if ($action === 'get_evaluation') {
        checkRole(['admin', 'mentor', 'student']);
        $student_id = (int)($_GET['student_id'] ?? 0);

        $stmt = $conn->prepare("SELECT * FROM evaluations WHERE student_id = ? ORDER BY evaluated_at DESC LIMIT 1");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        respond($stmt->get_result()->fetch_assoc());
    }

    // ---------------------------------------------------------
    // 6) DELETE ACTION
    // ---------------------------------------------------------
    if ($action === 'delete') {
        $type = $_GET['type'] ?? '';
        $id = (int)($_GET['id'] ?? 0);
        $user = $_SESSION['user'];

        if ($type === 'logs') $type = 'internship_logs';

        $allowedTypes = ['students', 'mentors', 'internship_logs'];
        if (!in_array($type, $allowedTypes, true) || $id <= 0) {
            respond(["success" => false, "error" => "พารามิเตอร์ไม่ถูกต้อง"], 400);
        }

        if ($type === 'mentors') {
            checkRole(['admin']);
            $delUser = $conn->prepare("DELETE FROM users WHERE role = 'mentor' AND ref_id = ?");
            $delUser->bind_param("i", $id);
            $delUser->execute();
            $delUser->close();
        } else if ($type === 'students') {
            checkRole(['admin']);
            $delUser = $conn->prepare("DELETE FROM users WHERE role = 'student' AND ref_id = ?");
            $delUser->bind_param("i", $id);
            $delUser->execute();
            $delUser->close();
        } else if ($type === 'internship_logs') {
            checkRole(['admin', 'student']);
            if ($user['role'] === 'student') {
                $checkStmt = $conn->prepare("SELECT student_id FROM internship_logs WHERE id = ?");
                $checkStmt->bind_param("i", $id);
                $checkStmt->execute();
                $logOwner = $checkStmt->get_result()->fetch_assoc();
                if (!$logOwner || $logOwner['student_id'] != $user['ref_id']) {
                    respond(["success" => false, "error" => "คุณไม่มีสิทธิ์ลบบันทึกนี้"], 403);
                }
            }
        }

        $stmt = $conn->prepare("DELETE FROM `$type` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        respond(["success" => true]);
    }

} catch (Throwable $e) {
    respond(["success" => false, "error" => $e->getMessage()], 500);
}
?>