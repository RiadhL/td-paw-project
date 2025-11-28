<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $userId = $stmt->fetchColumn();
    
    $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
    
    if ($userId) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting student: ' . $e->getMessage()]);
}
