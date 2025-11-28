<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('student');

$user = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.course_code
    FROM courses c
    JOIN student_courses sc ON c.id = sc.course_id
    WHERE sc.student_id = ?
");
$stmt->execute([$user['student_id']]);
$courses = $stmt->fetchAll();

$selectedCourse = $_GET['course_id'] ?? ($courses[0]['id'] ?? null);
$attendanceRecords = [];
$justifications = [];

if ($selectedCourse) {
    $stmt = $pdo->prepare("
        SELECT sess.id as session_id, sess.session_date, sess.session_number, 
               ar.status, ar.participation,
               j.id as justification_id, j.status as justification_status, j.reason as justification_reason
        FROM attendance_sessions sess
        LEFT JOIN attendance_records ar ON ar.session_id = sess.id AND ar.student_id = ?
        LEFT JOIN justifications j ON j.session_id = sess.id AND j.student_id = ?
        WHERE sess.course_id = ?
        ORDER BY sess.session_date, sess.session_number
    ");
    $stmt->execute([$user['student_id'], $user['student_id'], $selectedCourse]);
    $attendanceRecords = $stmt->fetchAll();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_justification'])) {
    $sessionId = $_POST['session_id'];
    $reason = $_POST['reason'];
    $filePath = null;
    
    if (isset($_FILES['justification_file']) && $_FILES['justification_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/justifications/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . $user['student_id'] . '_' . basename($_FILES['justification_file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['justification_file']['tmp_name'], $targetPath)) {
            $filePath = 'uploads/justifications/' . $fileName;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO justifications (student_id, session_id, reason, file_path, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user['student_id'], $sessionId, $reason, $filePath]);
        
        $message = 'Justification submitted successfully!';
        $messageType = 'success';
        
        $stmt = $pdo->prepare("
            SELECT sess.id as session_id, sess.session_date, sess.session_number, 
                   ar.status, ar.participation,
                   j.id as justification_id, j.status as justification_status, j.reason as justification_reason
            FROM attendance_sessions sess
            LEFT JOIN attendance_records ar ON ar.session_id = sess.id AND ar.student_id = ?
            LEFT JOIN justifications j ON j.session_id = sess.id AND j.student_id = ?
            WHERE sess.course_id = ?
            ORDER BY sess.session_date, sess.session_number
        ");
        $stmt->execute([$user['student_id'], $user['student_id'], $selectedCourse]);
        $attendanceRecords = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = 'Failed to submit justification. Please try again.';
        $messageType = 'error';
    }
}

$absences = count(array_filter($attendanceRecords, fn($r) => $r['status'] === 'absent'));
$participations = count(array_filter($attendanceRecords, fn($r) => $r['participation']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/student/index.php">Home</a></li>
            <li><a href="/student/attendance.php" class="active">My Attendance</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>My Attendance Details</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="course_id">Select Course</label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()">
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $selectedCourse == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="stats-row">
            <div class="stat-card <?php echo $absences < 3 ? 'good' : ($absences < 5 ? 'warning' : 'danger'); ?>">
                <h4>Absences</h4>
                <p class="stat-number"><?php echo $absences; ?></p>
            </div>
            <div class="stat-card">
                <h4>Participations</h4>
                <p class="stat-number"><?php echo $participations; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Sessions</h4>
                <p class="stat-number"><?php echo count($attendanceRecords); ?></p>
            </div>
        </div>

        <div class="card">
            <h3>Attendance Records</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Participation</th>
                        <th>Justification</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceRecords as $record): ?>
                    <tr class="<?php echo $record['status'] === 'absent' ? 'row-absent' : 'row-present'; ?>">
                        <td><?php echo date('d/m/Y', strtotime($record['session_date'])); ?></td>
                        <td>Session <?php echo $record['session_number']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $record['status'] === 'present' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($record['status'] ?? 'Not Recorded'); ?>
                            </span>
                        </td>
                        <td><?php echo $record['participation'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <?php if ($record['justification_id']): ?>
                                <span class="badge badge-<?php echo $record['justification_status'] === 'approved' ? 'success' : ($record['justification_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($record['justification_status']); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($record['status'] === 'absent' && !$record['justification_id']): ?>
                                <button class="btn btn-sm btn-primary" onclick="showJustificationForm(<?php echo $record['session_id']; ?>, '<?php echo date('d/m/Y', strtotime($record['session_date'])); ?>')">
                                    Submit Justification
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="justificationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h3>Submit Absence Justification</h3>
            <p>Session: <span id="modalSessionDate"></span></p>
            <form method="POST" enctype="multipart/form-data" id="justificationForm">
                <input type="hidden" name="submit_justification" value="1">
                <input type="hidden" name="session_id" id="modalSessionId">
                <input type="hidden" name="course_id" value="<?php echo $selectedCourse; ?>">
                
                <div class="form-group">
                    <label for="reason">Reason for Absence</label>
                    <textarea name="reason" id="reason" rows="4" required placeholder="Please explain the reason for your absence..."></textarea>
                    <span class="error-message" id="reason-error"></span>
                </div>
                
                <div class="form-group">
                    <label for="justification_file">Supporting Document (Optional)</label>
                    <input type="file" name="justification_file" id="justification_file" accept=".pdf,.jpg,.jpeg,.png">
                    <small>Accepted formats: PDF, JPG, PNG (Max 5MB)</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Justification</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
    <script>
        function showJustificationForm(sessionId, sessionDate) {
            $('#modalSessionId').val(sessionId);
            $('#modalSessionDate').text(sessionDate);
            $('#justificationModal').fadeIn();
        }
        
        function closeModal() {
            $('#justificationModal').fadeOut();
            $('#justificationForm')[0].reset();
        }
        
        $(document).click(function(e) {
            if ($(e.target).is('#justificationModal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>
