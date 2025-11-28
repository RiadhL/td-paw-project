<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

requireLogin();

try {
    $pdo = getDBConnection();
    
    $groupId = $_GET['group_id'] ?? null;
    $courseId = $_GET['course_id'] ?? null;
    
    $query = "
        SELECT s.id, s.student_id, s.fullname, s.matricule, s.email, s.group_id, g.group_name
        FROM students s
        LEFT JOIN groups g ON s.group_id = g.id
    ";
    
    $params = [];
    $conditions = [];
    
    if ($groupId) {
        $conditions[] = "s.group_id = ?";
        $params[] = $groupId;
    }
    
    if ($courseId) {
        $query .= " JOIN student_courses sc ON s.id = sc.student_id";
        $conditions[] = "sc.course_id = ?";
        $params[] = $courseId;
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY s.fullname";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'students' => $students]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching students: ' . $e->getMessage()]);
}
