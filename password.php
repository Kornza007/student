<!-- ?php
require_once 'db.php';

 ✏️ กำหนด Username และ รหัสผ่านที่คุณคิดขึ้นมาเองสำหรับแต่ละ Role ได้ตรงนี้
$accounts = [
    [
        'username' => 'admin',
        'password' => 'Admin01', // <-- รหัสผ่าน Admin ที่คุณตั้งเอง
        'role'     => 'admin',
        'ref_id'   => null
    ],
    [
        'username' => 'Mentor',
        'password' => 'Mentor01', // <-- รหัสผ่าน Mentor ที่คุณตั้งเอง
        'role'     => 'mentor',
        'ref_id'   => 1 // ผูกกับ id = 1 ในตาราง mentors
    ],
    [
        'username' => 'Student',
        'password' => 'Student01', // <-- รหัสผ่าน Student ที่คุณตั้งเอง
        'role'     => 'student',
        'ref_id'   => 1 // ผูกกับ id = 1 ในตาราง students
    ]
];

foreach ($accounts as $acc) {
    // 1. เข้ารหัสผ่านรหัสผ่านด้วย password_hash ก่อนบันทึก
    $hashedPassword = password_hash($acc['password'], PASSWORD_BCRYPT);

    // 2. บันทึกหรืออัปเดตลงตาราง users
    $username = $acc['username'];
    $role = $acc['role'];
    $ref_id = $acc['ref_id']; // อาจเป็น null

    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role, ref_id) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), ref_id = VALUES(ref_id)
    ");
    
    // bind_param เพื่ออนุญาต NULL value
    if ($ref_id === null) {
        $stmt->bind_param("sssi", $username, $hashedPassword, $role, $ref_id);
    } else {
        $stmt->bind_param("sssi", $username, $hashedPassword, $role, $ref_id);
    }
    
    $stmt->execute();
    $stmt->close();

    echo "สร้าง/อัปเดต บัญชี <b>{$acc['username']}</b> (Role: {$acc['role']}) เรียบร้อยแล้ว!<br>";
}
? -->