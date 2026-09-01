<?php
require __DIR__ . '/audit_helper.php';
global $conn;

$tables = ['students', 'mentors', 'internship_logs', 'evaluations', 'users', 'audit_logs'];
foreach ($tables as $t) {
    echo "=== TABLE: $t ===" . PHP_EOL;
    $r = $conn->query("DESCRIBE `$t`");
    if (!$r) { echo "  ERROR: " . $conn->error . PHP_EOL; continue; }
    while ($row = $r->fetch_assoc()) {
        echo "  " . str_pad($row['Field'], 20) . " " . str_pad($row['Type'], 25) . " " . $row['Null'] . " " . $row['Key'] . PHP_EOL;
    }
    echo PHP_EOL;
}
?>
