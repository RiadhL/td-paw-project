<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$fullname = $data['fullname'] ?? '';
$matricule = $data['matricule'] ?? '';
$email = $data['email'] ?? '';
$groupId = $data['group_id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("UPDATE students SET fullname = ?, matricule = ?, email = ?, group_id = ? WHERE id = ?");
    $stmt->execute([$fullname, $matricule, $email, $groupId ?: null, $id]);
    
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating student: ' . $e->getMessage()]);
}
