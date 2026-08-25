<?php
// login.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, role, ref_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        // แก้ไขให้เก็บ session แบบ Array เพื่อให้ตรงกับ api.php
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
            'ref_id'   => $user['ref_id']
        ];

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(401);
echo json_encode(['success' => false, 'error' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
?>