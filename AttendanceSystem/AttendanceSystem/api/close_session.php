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

$sessionId = $data['session_id'] ?? null;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ? AND opened_by = ?");
    $result = $stmt->execute([$sessionId, $user['professor_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Session closed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Session not found or you do not have permission to close it']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error closing session: ' . $e->getMessage()]);
}
