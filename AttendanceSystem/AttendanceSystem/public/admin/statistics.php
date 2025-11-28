<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('admin');

$user = getCurrentUser();
$pdo = getDBConnection();

$courseStats = $pdo->query("
    SELECT c.course_name, c.course_code,
           COUNT(DISTINCT sc.student_id) as total_students,
           COUNT(DISTINCT sess.id) as total_sessions,
           COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as total_present,
           COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as total_absent
    FROM courses c
    LEFT JOIN student_courses sc ON c.id = sc.course_id
    LEFT JOIN attendance_sessions sess ON c.id = sess.course_id
    LEFT JOIN attendance_records ar ON sess.id = ar.session_id
    GROUP BY c.id, c.course_name, c.course_code
    ORDER BY c.course_name
")->fetchAll();

$attendanceByGroup = $pdo->query("
    SELECT g.group_name, c.course_name,
           COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as present_count,
           COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as absent_count
    FROM groups g
    JOIN courses c ON g.course_id = c.id
    LEFT JOIN attendance_sessions sess ON g.id = sess.group_id
    LEFT JOIN attendance_records ar ON sess.id = ar.session_id
    GROUP BY g.id, g.group_name, c.course_name
    ORDER BY c.course_name, g.group_name
")->fetchAll();

$studentsAtRisk = $pdo->query("
    SELECT s.fullname, s.matricule,
           COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as absence_count
    FROM students s
    LEFT JOIN attendance_records ar ON s.id = ar.student_id
    GROUP BY s.id, s.fullname, s.matricule
    HAVING COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) >= 3
    ORDER BY absence_count DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/admin/index.php">Home</a></li>
            <li><a href="/admin/statistics.php" class="active">Statistics</a></li>
            <li><a href="/admin/students.php">Manage Students</a></li>
            <li><a href="/admin/justifications.php">Justifications</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Attendance Statistics</h1>

        <div class="charts-grid">
            <div class="card">
                <h3>Attendance by Course</h3>
                <canvas id="courseChart"></canvas>
            </div>

            <div class="card">
                <h3>Attendance Distribution</h3>
                <canvas id="distributionChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h3>Course Statistics</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Students</th>
                        <th>Sessions</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courseStats as $course): 
                        $total = $course['total_present'] + $course['total_absent'];
                        $rate = $total > 0 ? round(($course['total_present'] / $total) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo $course['total_students']; ?></td>
                        <td><?php echo $course['total_sessions']; ?></td>
                        <td><?php echo $course['total_present']; ?></td>
                        <td><?php echo $course['total_absent']; ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $rate; ?>%"></div>
                                <span><?php echo $rate; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Students at Risk (3+ Absences)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Matricule</th>
                        <th>Absences</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentsAtRisk as $student): 
                        $status = $student['absence_count'] >= 5 ? 'Excluded' : 'Warning';
                        $statusClass = $student['absence_count'] >= 5 ? 'danger' : 'warning';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                        <td><?php echo $student['absence_count']; ?></td>
                        <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const courseData = <?php echo json_encode($courseStats); ?>;
        
        const ctx1 = document.getElementById('courseChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: courseData.map(c => c.course_code),
                datasets: [{
                    label: 'Present',
                    data: courseData.map(c => c.total_present),
                    backgroundColor: '#4CAF50'
                }, {
                    label: 'Absent',
                    data: courseData.map(c => c.total_absent),
                    backgroundColor: '#f44336'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const totalPresent = courseData.reduce((sum, c) => sum + parseInt(c.total_present), 0);
        const totalAbsent = courseData.reduce((sum, c) => sum + parseInt(c.total_absent), 0);
        
        const ctx2 = document.getElementById('distributionChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [totalPresent, totalAbsent],
                    backgroundColor: ['#4CAF50', '#f44336']
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>
