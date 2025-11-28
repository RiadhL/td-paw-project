<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('professor');

$user = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.course_code,
           (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = c.id) as session_count
    FROM courses c
    WHERE c.professor_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$user['professor_id']]);
$courses = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT s.id, s.session_date, s.session_number, s.status, c.course_name, c.course_code, g.group_name
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN groups g ON s.group_id = g.id
    WHERE c.professor_id = ?
    ORDER BY s.session_date DESC, s.session_number DESC
    LIMIT 10
");
$stmt->execute([$user['professor_id']]);
$recentSessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/professor/index.php" class="active">Home</a></li>
            <li><a href="/professor/mark_attendance.php">Mark Attendance</a></li>
            <li><a href="/professor/attendance_summary.php">Summary</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Professor Dashboard</h1>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>My Courses</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Sessions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo $course['session_count']; ?></td>
                            <td>
                                <a href="/professor/mark_attendance.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">New Session</a>
                                <a href="/professor/attendance_summary.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-secondary">View Summary</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>Recent Sessions</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Group</th>
                            <th>Session</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSessions as $session): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($session['session_date'])); ?></td>
                            <td><?php echo htmlspecialchars($session['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($session['group_name'] ?? 'All'); ?></td>
                            <td>Session <?php echo $session['session_number']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $session['status'] === 'open' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($session['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
