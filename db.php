<?php
// db.php - Database Connection
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = ''; 
$db   = 'lab_month_korn'; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error], JSON_UNESCAPED_UNICODE));
}

$conn->set_charset("utf8mb4");

function getList($table) {
    global $conn;
    $allowed_tables = ['students', 'mentors', 'internship_logs', 'users', 'evaluations'];
    if (!in_array($table, $allowed_tables, true)) {
        return [];
    }
    
    $sql = "SELECT * FROM `$table` ORDER BY id DESC";
    $result = $conn->query($sql);
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}
?>