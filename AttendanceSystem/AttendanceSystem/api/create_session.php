<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

requireRole('professor');

$user = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);

$courseId = $data['course_id'] ?? null;
$groupId = $data['group_id'] ?? null;

if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(session_number), 0) + 1 FROM attendance_sessions WHERE course_id = ? AND session_date = CURRENT_DATE");
    $stmt->execute([$courseId]);
    $sessionNumber = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        INSERT INTO attendance_sessions (course_id, group_id, session_date, session_number, opened_by, status) 
        VALUES (?, ?, CURRENT_DATE, ?, ?, 'open') 
        RETURNING id
    ");
    $stmt->execute([$courseId, $groupId, $sessionNumber, $user['professor_id']]);
    $sessionId = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Session created successfully',
        'session_id' => $sessionId
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating session: ' . $e->getMessage()]);
}
