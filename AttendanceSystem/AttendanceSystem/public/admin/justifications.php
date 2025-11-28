<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('admin');

$user = getCurrentUser();
$pdo = getDBConnection();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $justificationId = $_POST['justification_id'];
    $action = $_POST['action'];
    
    try {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE justifications SET status = ?, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ? WHERE id = ?");
        $stmt->execute([$newStatus, $user['id'], $justificationId]);
        
        $message = "Justification $newStatus successfully!";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "Error updating justification: " . $e->getMessage();
        $messageType = 'error';
    }
}

$statusFilter = $_GET['status'] ?? 'pending';

$query = "
    SELECT j.id, j.reason, j.file_path, j.status, j.submitted_at,
           s.fullname as student_name, s.matricule,
           c.course_name, c.course_code,
           sess.session_date, sess.session_number
    FROM justifications j
    JOIN students s ON j.student_id = s.id
    JOIN attendance_sessions sess ON j.session_id = sess.id
    JOIN courses c ON sess.course_id = c.id
";

if ($statusFilter !== 'all') {
    $query .= " WHERE j.status = ?";
}
$query .= " ORDER BY j.submitted_at DESC";

$stmt = $pdo->prepare($query);
if ($statusFilter !== 'all') {
    $stmt->execute([$statusFilter]);
} else {
    $stmt->execute();
}
$justifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justifications - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/admin/index.php">Home</a></li>
            <li><a href="/admin/statistics.php">Statistics</a></li>
            <li><a href="/admin/students.php">Manage Students</a></li>
            <li><a href="/admin/justifications.php" class="active">Justifications</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Absence Justifications</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="filter-tabs">
                <a href="?status=pending" class="tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=approved" class="tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?status=rejected" class="tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
                <a href="?status=all" class="tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
            </div>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Session Date</th>
                        <th>Reason</th>
                        <th>Document</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <?php if ($statusFilter === 'pending'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($justifications as $just): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($just['student_name']); ?><br>
                            <small><?php echo htmlspecialchars($just['matricule']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($just['course_code']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($just['session_date'])); ?></td>
                        <td class="reason-cell"><?php echo htmlspecialchars(substr($just['reason'], 0, 100)) . (strlen($just['reason']) > 100 ? '...' : ''); ?></td>
                        <td>
                            <?php if ($just['file_path']): ?>
                                <a href="/<?php echo htmlspecialchars($just['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($just['submitted_at'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $just['status'] === 'approved' ? 'success' : ($just['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst($just['status']); ?>
                            </span>
                        </td>
                        <?php if ($statusFilter === 'pending'): ?>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="justification_id" value="<?php echo $just['id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($justifications)): ?>
                <p class="text-center">No justifications found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
