<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

requireRole(['admin', 'professor']);

$data = json_decode(file_get_contents('php://input'), true);

$studentId = $data['student_id'] ?? '';
$fullname = $data['fullname'] ?? '';
$matricule = $data['matricule'] ?? '';
$email = $data['email'] ?? '';
$groupId = $data['group_id'] ?? null;

if (empty($studentId) || empty($fullname) || empty($matricule)) {
    echo json_encode(['success' => false, 'message' => 'Student ID, name, and matricule are required']);
    exit;
}

if (!preg_match('/^\d+$/', $studentId)) {
    echo json_encode(['success' => false, 'message' => 'Student ID must contain only numbers']);
    exit;
}

if (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
    echo json_encode(['success' => false, 'message' => 'Name must contain only letters']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $password = password_hash('student123', PASSWORD_DEFAULT);
    $username = strtolower(str_replace(' ', '_', $fullname));
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, fullname, email) VALUES (?, ?, 'student', ?, ?) RETURNING id");
    $stmt->execute([$username, $password, $fullname, $email]);
    $userId = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, matricule, fullname, group_id, email) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $studentId, $matricule, $fullname, $groupId ?: null, $email]);
    
    echo json_encode(['success' => true, 'message' => 'Student added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error adding student: ' . $e->getMessage()]);
}
