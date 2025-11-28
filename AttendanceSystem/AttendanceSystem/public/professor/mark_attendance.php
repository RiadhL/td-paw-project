<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('professor');

$user = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT id, course_name, course_code FROM courses WHERE professor_id = ?");
$stmt->execute([$user['professor_id']]);
$courses = $stmt->fetchAll();

$selectedCourse = $_GET['course_id'] ?? null;
$selectedGroup = $_GET['group_id'] ?? null;
$sessionId = $_GET['session_id'] ?? null;

$groups = [];
$students = [];
$currentSession = null;

if ($selectedCourse) {
    $stmt = $pdo->prepare("SELECT id, group_name FROM groups WHERE course_id = ?");
    $stmt->execute([$selectedCourse]);
    $groups = $stmt->fetchAll();
}

if ($sessionId) {
    $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE id = ? AND opened_by = ?");
    $stmt->execute([$sessionId, $user['professor_id']]);
    $currentSession = $stmt->fetch();
    
    if ($currentSession) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.student_id, s.fullname, s.matricule,
                   COALESCE(ar.status, 'absent') as attendance_status,
                   COALESCE(ar.participation, false) as participation
            FROM students s
            JOIN student_courses sc ON s.id = sc.student_id
            LEFT JOIN attendance_records ar ON ar.student_id = s.id AND ar.session_id = ?
            WHERE sc.course_id = ? " . ($currentSession['group_id'] ? "AND sc.group_id = ?" : "") . "
            ORDER BY s.fullname
        ");
        $params = [$sessionId, $currentSession['course_id']];
        if ($currentSession['group_id']) {
            $params[] = $currentSession['group_id'];
        }
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    }
} elseif ($selectedCourse && ($selectedGroup || count($groups) === 0)) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_id, s.fullname, s.matricule
        FROM students s
        JOIN student_courses sc ON s.id = sc.student_id
        WHERE sc.course_id = ? " . ($selectedGroup ? "AND sc.group_id = ?" : "") . "
        ORDER BY s.fullname
    ");
    $params = [$selectedCourse];
    if ($selectedGroup) {
        $params[] = $selectedGroup;
    }
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_session'])) {
        $courseId = $_POST['course_id'];
        $groupId = $_POST['group_id'] ?: null;
        
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(session_number), 0) + 1 FROM attendance_sessions WHERE course_id = ? AND session_date = CURRENT_DATE");
        $stmt->execute([$courseId]);
        $sessionNumber = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO attendance_sessions (course_id, group_id, session_date, session_number, opened_by, status) VALUES (?, ?, CURRENT_DATE, ?, ?, 'open') RETURNING id");
        $stmt->execute([$courseId, $groupId, $sessionNumber, $user['professor_id']]);
        $newSessionId = $stmt->fetchColumn();
        
        header("Location: /professor/mark_attendance.php?session_id=$newSessionId");
        exit;
    }
    
    if (isset($_POST['save_attendance'])) {
        $sessionId = $_POST['session_id'];
        $attendance = $_POST['attendance'] ?? [];
        $participation = $_POST['participation'] ?? [];
        
        foreach ($attendance as $studentId => $status) {
            $hasParticipation = isset($participation[$studentId]) ? true : false;
            
            $stmt = $pdo->prepare("
                INSERT INTO attendance_records (session_id, student_id, status, participation)
                VALUES (?, ?, ?, ?)
                ON CONFLICT (session_id, student_id) 
                DO UPDATE SET status = EXCLUDED.status, participation = EXCLUDED.participation
            ");
            $stmt->execute([$sessionId, $studentId, $status, $hasParticipation]);
        }
        
        $message = "Attendance saved successfully!";
    }
    
    if (isset($_POST['close_session'])) {
        $sessionId = $_POST['session_id'];
        $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ? AND opened_by = ?");
        $stmt->execute([$sessionId, $user['professor_id']]);
        
        header("Location: /professor/index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/professor/index.php">Home</a></li>
            <li><a href="/professor/mark_attendance.php" class="active">Mark Attendance</a></li>
            <li><a href="/professor/attendance_summary.php">Summary</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Mark Attendance</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!$sessionId): ?>
        <div class="card">
            <h3>Start New Attendance Session</h3>
            <form method="POST" id="createSessionForm">
                <input type="hidden" name="create_session" value="1">
                
                <div class="form-group">
                    <label for="course_id">Select Course</label>
                    <select name="course_id" id="course_id" required onchange="loadGroups(this.value)">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $selectedCourse == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="group_id">Select Group (Optional)</label>
                    <select name="group_id" id="group_id">
                        <option value="">All Groups</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Start Session</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($currentSession && count($students) > 0): ?>
        <div class="card">
            <h3>Session: <?php echo date('d/m/Y', strtotime($currentSession['session_date'])); ?> - Session <?php echo $currentSession['session_number']; ?></h3>
            <span class="badge badge-<?php echo $currentSession['status'] === 'open' ? 'success' : 'secondary'; ?>">
                <?php echo ucfirst($currentSession['status']); ?>
            </span>
            
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="save_attendance" value="1">
                <input type="hidden" name="session_id" value="<?php echo $currentSession['id']; ?>">
                
                <div class="table-controls">
                    <input type="text" id="searchStudent" placeholder="Search by Name..." class="search-input">
                    <button type="button" id="markAllPresent" class="btn btn-sm btn-success">Mark All Present</button>
                    <button type="button" id="markAllAbsent" class="btn btn-sm btn-danger">Mark All Absent</button>
                </div>
                
                <table class="table attendance-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Student Name</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Participation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr data-student-name="<?php echo strtolower($student['fullname']); ?>">
                            <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                            <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                            <td>
                                <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="present" 
                                    <?php echo $student['attendance_status'] === 'present' ? 'checked' : ''; ?>>
                            </td>
                            <td>
                                <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="absent"
                                    <?php echo $student['attendance_status'] === 'absent' ? 'checked' : ''; ?>>
                            </td>
                            <td>
                                <input type="checkbox" name="participation[<?php echo $student['id']; ?>]" value="1"
                                    <?php echo $student['participation'] ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                    <?php if ($currentSession['status'] === 'open'): ?>
                    <button type="submit" name="close_session" value="1" class="btn btn-warning" onclick="return confirm('Are you sure you want to close this session?')">Close Session</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php elseif ($sessionId): ?>
            <div class="alert alert-warning">No students found for this session.</div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
    <script>
        function loadGroups(courseId) {
            if (courseId) {
                window.location.href = '/professor/mark_attendance.php?course_id=' + courseId;
            }
        }
    </script>
</body>
</html>
