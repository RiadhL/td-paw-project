<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('student');

$user = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.course_code, g.group_name,
           p.fullname as professor_name,
           (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = c.id) as total_sessions,
           (SELECT COUNT(*) FROM attendance_records ar 
            JOIN attendance_sessions sess ON ar.session_id = sess.id 
            WHERE ar.student_id = ? AND sess.course_id = c.id AND ar.status = 'absent') as absences
    FROM courses c
    JOIN student_courses sc ON c.id = sc.course_id
    LEFT JOIN groups g ON sc.group_id = g.id
    LEFT JOIN professors p ON c.professor_id = p.id
    WHERE sc.student_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$user['student_id'], $user['student_id']]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/student/index.php" class="active">Home</a></li>
            <li><a href="/student/attendance.php">My Attendance</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>My Enrolled Courses</h1>
        
        <div class="courses-grid">
            <?php foreach ($courses as $course): 
                $attendanceRate = $course['total_sessions'] > 0 
                    ? round((($course['total_sessions'] - $course['absences']) / $course['total_sessions']) * 100) 
                    : 100;
                $statusClass = $course['absences'] < 3 ? 'good' : ($course['absences'] < 5 ? 'warning' : 'danger');
            ?>
            <div class="course-card">
                <div class="course-header">
                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                </div>
                <div class="course-info">
                    <p><strong>Professor:</strong> <?php echo htmlspecialchars($course['professor_name'] ?? 'TBA'); ?></p>
                    <p><strong>Group:</strong> <?php echo htmlspecialchars($course['group_name'] ?? 'N/A'); ?></p>
                    <p><strong>Sessions:</strong> <?php echo $course['total_sessions']; ?></p>
                </div>
                <div class="attendance-summary status-<?php echo $statusClass; ?>">
                    <div class="attendance-bar">
                        <div class="attendance-fill" style="width: <?php echo $attendanceRate; ?>%"></div>
                    </div>
                    <p>Attendance: <?php echo $attendanceRate; ?>% | Absences: <?php echo $course['absences']; ?></p>
                </div>
                <a href="/student/attendance.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-block">View Details</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
