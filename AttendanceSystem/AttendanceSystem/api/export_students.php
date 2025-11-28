<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

requireRole('admin');

$pdo = getDBConnection();

$students = $pdo->query("
    SELECT s.student_id, s.fullname, s.matricule, s.email, g.group_name
    FROM students s
    LEFT JOIN groups g ON s.group_id = g.id
    ORDER BY s.fullname
")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Student ID', 'Full Name', 'Matricule', 'Email', 'Group'], ';');

foreach ($students as $student) {
    fputcsv($output, [
        $student['student_id'],
        $student['fullname'],
        $student['matricule'],
        $student['email'] ?? '',
        $student['group_name'] ?? ''
    ], ';');
}

fclose($output);
exit;
