<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('admin');

$user = getCurrentUser();
$pdo = getDBConnection();

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalProfessors = $pdo->query("SELECT COUNT(*) FROM professors")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalSessions = $pdo->query("SELECT COUNT(*) FROM attendance_sessions")->fetchColumn();
$pendingJustifications = $pdo->query("SELECT COUNT(*) FROM justifications WHERE status = 'pending'")->fetchColumn();

$recentSessions = $pdo->query("
    SELECT s.session_date, s.session_number, s.status, c.course_name, p.fullname as professor
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    JOIN professors p ON s.opened_by = p.id
    ORDER BY s.created_at DESC
    LIMIT 5
")->fetchAll();

$recentJustifications = $pdo->query("
    SELECT j.reason, j.status, j.submitted_at, s.fullname as student, c.course_name
    FROM justifications j
    JOIN students s ON j.student_id = s.id
    JOIN attendance_sessions sess ON j.session_id = sess.id
    JOIN courses c ON sess.course_id = c.id
    ORDER BY j.submitted_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/admin/index.php" class="active">Home</a></li>
            <li><a href="/admin/statistics.php">Statistics</a></li>
            <li><a href="/admin/students.php">Manage Students</a></li>
            <li><a href="/admin/justifications.php">Justifications</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Administrator Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Students</h4>
                <p class="stat-number"><?php echo $totalStudents; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Professors</h4>
                <p class="stat-number"><?php echo $totalProfessors; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Courses</h4>
                <p class="stat-number"><?php echo $totalCourses; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Sessions</h4>
                <p class="stat-number"><?php echo $totalSessions; ?></p>
            </div>
            <div class="stat-card warning">
                <h4>Pending Justifications</h4>
                <p class="stat-number"><?php echo $pendingJustifications; ?></p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Recent Sessions</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Professor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSessions as $session): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($session['session_date'])); ?></td>
                            <td><?php echo htmlspecialchars($session['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($session['professor']); ?></td>
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

            <div class="card">
                <h3>Recent Justifications</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentJustifications as $just): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($just['student']); ?></td>
                            <td><?php echo htmlspecialchars($just['course_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $just['status'] === 'approved' ? 'success' : ($just['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($just['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="/admin/justifications.php" class="btn btn-primary">View All</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
