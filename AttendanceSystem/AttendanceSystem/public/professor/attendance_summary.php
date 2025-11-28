<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('professor');

$user = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT id, course_name, course_code FROM courses WHERE professor_id = ?");
$stmt->execute([$user['professor_id']]);
$courses = $stmt->fetchAll();

$selectedCourse = $_GET['course_id'] ?? ($courses[0]['id'] ?? null);
$selectedGroup = $_GET['group_id'] ?? null;

$groups = [];
$summaryData = [];
$sessions = [];

if ($selectedCourse) {
    $stmt = $pdo->prepare("SELECT id, group_name FROM groups WHERE course_id = ?");
    $stmt->execute([$selectedCourse]);
    $groups = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT id, session_number, session_date 
        FROM attendance_sessions 
        WHERE course_id = ? " . ($selectedGroup ? "AND group_id = ?" : "") . "
        ORDER BY session_number
    ");
    $params = [$selectedCourse];
    if ($selectedGroup) $params[] = $selectedGroup;
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT 
            s.id as student_id,
            s.fullname,
            s.matricule,
            COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as absences,
            COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as presences,
            COUNT(CASE WHEN ar.participation = true THEN 1 END) as participations,
            COUNT(ar.id) as total_sessions
        FROM students s
        JOIN student_courses sc ON s.id = sc.student_id
        LEFT JOIN attendance_records ar ON ar.student_id = s.id
        LEFT JOIN attendance_sessions sess ON ar.session_id = sess.id AND sess.course_id = ?
        WHERE sc.course_id = ? " . ($selectedGroup ? "AND sc.group_id = ?" : "") . "
        GROUP BY s.id, s.fullname, s.matricule
        ORDER BY s.fullname
    ");
    $params = [$selectedCourse, $selectedCourse];
    if ($selectedGroup) $params[] = $selectedGroup;
    $stmt->execute($params);
    $summaryData = $stmt->fetchAll();

    foreach ($summaryData as &$student) {
        $stmt = $pdo->prepare("
            SELECT sess.session_number, ar.status, ar.participation
            FROM attendance_records ar
            JOIN attendance_sessions sess ON ar.session_id = sess.id
            WHERE ar.student_id = ? AND sess.course_id = ?
            ORDER BY sess.session_number
        ");
        $stmt->execute([$student['student_id'], $selectedCourse]);
        $student['sessions'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stmt = $pdo->prepare("
            SELECT sess.session_number, ar.participation
            FROM attendance_records ar
            JOIN attendance_sessions sess ON ar.session_id = sess.id
            WHERE ar.student_id = ? AND sess.course_id = ?
            ORDER BY sess.session_number
        ");
        $stmt->execute([$student['student_id'], $selectedCourse]);
        $participationData = $stmt->fetchAll();
        $student['participation_sessions'] = [];
        foreach ($participationData as $p) {
            $student['participation_sessions'][$p['session_number']] = $p['participation'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/professor/index.php">Home</a></li>
            <li><a href="/professor/mark_attendance.php">Mark Attendance</a></li>
            <li><a href="/professor/attendance_summary.php" class="active">Summary</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Attendance Summary</h1>

        <div class="card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()">
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $selectedCourse == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="group_id">Group</label>
                    <select name="group_id" id="group_id" onchange="this.form.submit()">
                        <option value="">All Groups</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo $selectedGroup == $group['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['group_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-controls">
                <input type="text" id="searchName" placeholder="Search by Name..." class="search-input">
                <button type="button" id="sortAbsences" class="btn btn-sm btn-secondary">Sort by Absences (Asc)</button>
                <button type="button" id="sortParticipation" class="btn btn-sm btn-secondary">Sort by Participation (Desc)</button>
                <button type="button" id="highlightExcellent" class="btn btn-sm btn-success">Highlight Excellent</button>
                <button type="button" id="resetColors" class="btn btn-sm btn-warning">Reset Colors</button>
                <button type="button" id="showReport" class="btn btn-sm btn-info">Show Report</button>
            </div>
            
            <p id="sortStatus" class="sort-status"></p>

            <div class="table-responsive">
                <table class="table summary-table" id="summaryTable">
                    <thead>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <?php for ($i = 1; $i <= count($sessions); $i++): ?>
                                <th colspan="2">S<?php echo $i; ?></th>
                            <?php endfor; ?>
                            <th>Absences</th>
                            <th>Participation</th>
                            <th>Message</th>
                        </tr>
                        <tr class="sub-header">
                            <th></th>
                            <th></th>
                            <?php for ($i = 1; $i <= count($sessions); $i++): ?>
                                <th>P</th>
                                <th>Pa</th>
                            <?php endfor; ?>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summaryData as $student): 
                            $nameParts = explode(' ', $student['fullname'], 2);
                            $lastName = $nameParts[0] ?? '';
                            $firstName = $nameParts[1] ?? '';
                        ?>
                        <tr data-absences="<?php echo $student['absences']; ?>" data-participation="<?php echo $student['participations']; ?>">
                            <td class="last-name"><?php echo htmlspecialchars($lastName); ?></td>
                            <td class="first-name"><?php echo htmlspecialchars($firstName); ?></td>
                            <?php for ($i = 1; $i <= count($sessions); $i++): 
                                $status = $student['sessions'][$i] ?? 'absent';
                                $participated = $student['participation_sessions'][$i] ?? false;
                            ?>
                                <td class="attendance-cell"><?php echo $status === 'present' ? '&#10003;' : ''; ?></td>
                                <td class="participation-cell"><?php echo $participated ? '&#10003;' : ''; ?></td>
                            <?php endfor; ?>
                            <td class="absences-count"><?php echo $student['absences']; ?> Abs</td>
                            <td class="participation-count"><?php echo $student['participations']; ?> Par</td>
                            <td class="message-cell"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="reportSection" class="card" style="display: none;">
            <h3>Attendance Report</h3>
            <div class="report-stats">
                <div class="stat-box">
                    <h4>Total Students</h4>
                    <p id="totalStudents">0</p>
                </div>
                <div class="stat-box">
                    <h4>Average Attendance</h4>
                    <p id="avgAttendance">0%</p>
                </div>
                <div class="stat-box">
                    <h4>Students with Participation</h4>
                    <p id="studentsParticipated">0</p>
                </div>
            </div>
            <canvas id="attendanceChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/attendance.js"></script>
</body>
</html>
