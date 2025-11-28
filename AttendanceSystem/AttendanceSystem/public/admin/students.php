<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_connect.php';

requireRole('admin');

$user = getCurrentUser();
$pdo = getDBConnection();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        $studentId = $_POST['student_id'];
        $fullname = $_POST['fullname'];
        $matricule = $_POST['matricule'];
        $email = $_POST['email'];
        $groupId = $_POST['group_id'] ?: null;
        
        try {
            $password = password_hash('student123', PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '_', $fullname));
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, fullname, email) VALUES (?, ?, 'student', ?, ?) RETURNING id");
            $stmt->execute([$username, $password, $fullname, $email]);
            $userId = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, matricule, fullname, group_id, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $studentId, $matricule, $fullname, $groupId, $email]);
            
            $message = "Student added successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Error adding student: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['delete_student'])) {
        $studentDbId = $_POST['student_db_id'];
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$studentDbId]);
            $userId = $stmt->fetchColumn();
            
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$studentDbId]);
            if ($userId) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            }
            
            $message = "Student deleted successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Error deleting student: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['update_student'])) {
        $studentDbId = $_POST['student_db_id'];
        $fullname = $_POST['fullname'];
        $matricule = $_POST['matricule'];
        $email = $_POST['email'];
        $groupId = $_POST['group_id'] ?: null;
        
        try {
            $stmt = $pdo->prepare("UPDATE students SET fullname = ?, matricule = ?, email = ?, group_id = ? WHERE id = ?");
            $stmt->execute([$fullname, $matricule, $email, $groupId, $studentDbId]);
            
            $message = "Student updated successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Error updating student: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['import_students']) && isset($_FILES['excel_file'])) {
        $file = $_FILES['excel_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($file['tmp_name']);
            $lines = explode("\n", $fileContent);
            $imported = 0;
            $errors = 0;
            
            foreach ($lines as $index => $line) {
                if ($index === 0 || empty(trim($line))) continue;
                
                $data = str_getcsv($line, ';');
                if (count($data) >= 3) {
                    try {
                        $studentId = trim($data[0]);
                        $fullname = trim($data[1]);
                        $matricule = trim($data[2]);
                        $email = isset($data[3]) ? trim($data[3]) : '';
                        
                        $password = password_hash('student123', PASSWORD_DEFAULT);
                        $username = strtolower(str_replace(' ', '_', $fullname)) . '_' . $studentId;
                        
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, fullname, email) VALUES (?, ?, 'student', ?, ?) RETURNING id");
                        $stmt->execute([$username, $password, $fullname, $email]);
                        $userId = $stmt->fetchColumn();
                        
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, matricule, fullname, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$userId, $studentId, $matricule, $fullname, $email]);
                        
                        $imported++;
                    } catch (Exception $e) {
                        $errors++;
                    }
                }
            }
            
            $message = "Import completed: $imported students imported, $errors errors.";
            $messageType = $errors > 0 ? 'warning' : 'success';
        }
    }
}

$students = $pdo->query("
    SELECT s.id, s.student_id, s.fullname, s.matricule, s.email, s.group_id, g.group_name
    FROM students s
    LEFT JOIN groups g ON s.group_id = g.id
    ORDER BY s.fullname
")->fetchAll();

$groups = $pdo->query("SELECT id, group_name FROM groups ORDER BY group_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Algiers University - Attendance System</div>
        <ul class="nav-menu">
            <li><a href="/admin/index.php">Home</a></li>
            <li><a href="/admin/statistics.php">Statistics</a></li>
            <li><a href="/admin/students.php" class="active">Manage Students</a></li>
            <li><a href="/admin/justifications.php">Justifications</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
        <div class="nav-user">Welcome, <?php echo htmlspecialchars($user['fullname']); ?></div>
    </nav>

    <div class="container">
        <h1>Student Management</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="showAddForm()">Add New Student</button>
            <button class="btn btn-secondary" onclick="showImportForm()">Import from Excel</button>
            <a href="/api/export_students.php" class="btn btn-info">Export to Excel</a>
        </div>

        <div id="addStudentForm" class="card form-card" style="display: none;">
            <h3>Add New Student</h3>
            <form method="POST" id="studentForm">
                <input type="hidden" name="add_student" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" required>
                        <span class="error-message" id="student_id-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="matricule">Matricule</label>
                        <input type="text" id="matricule" name="matricule" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" required>
                    <span class="error-message" id="fullname-error"></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                        <span class="error-message" id="email-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="group_id">Group</label>
                        <select id="group_id" name="group_id">
                            <option value="">-- Select Group --</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Student</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Cancel</button>
            </form>
        </div>

        <div id="importForm" class="card form-card" style="display: none;">
            <h3>Import Students from Excel (CSV format)</h3>
            <p>CSV format: student_id;fullname;matricule;email</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="import_students" value="1">
                
                <div class="form-group">
                    <label for="excel_file">Select File</label>
                    <input type="file" id="excel_file" name="excel_file" accept=".csv,.txt" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Import</button>
                <button type="button" class="btn btn-secondary" onclick="hideImportForm()">Cancel</button>
            </form>
        </div>

        <div class="card">
            <div class="table-controls">
                <input type="text" id="searchStudent" placeholder="Search by Name..." class="search-input">
            </div>
            
            <table class="table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Matricule</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Group</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr data-student-name="<?php echo strtolower($student['fullname']); ?>">
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                        <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($student['group_name'] ?? 'N/A'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-secondary" onclick='editStudent(<?php echo json_encode($student); ?>)'>Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                <input type="hidden" name="delete_student" value="1">
                                <input type="hidden" name="student_db_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Student</h3>
            <form method="POST" id="editStudentForm">
                <input type="hidden" name="update_student" value="1">
                <input type="hidden" name="student_db_id" id="edit_student_db_id">
                
                <div class="form-group">
                    <label for="edit_fullname">Full Name</label>
                    <input type="text" id="edit_fullname" name="fullname" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_matricule">Matricule</label>
                    <input type="text" id="edit_matricule" name="matricule" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="edit_group_id">Group</label>
                    <select id="edit_group_id" name="group_id">
                        <option value="">-- Select Group --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Student</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/validation.js"></script>
    <script>
        function showAddForm() {
            $('#addStudentForm').slideDown();
            $('#importForm').slideUp();
        }
        
        function hideAddForm() {
            $('#addStudentForm').slideUp();
        }
        
        function showImportForm() {
            $('#importForm').slideDown();
            $('#addStudentForm').slideUp();
        }
        
        function hideImportForm() {
            $('#importForm').slideUp();
        }
        
        function editStudent(student) {
            $('#edit_student_db_id').val(student.id);
            $('#edit_fullname').val(student.fullname);
            $('#edit_matricule').val(student.matricule);
            $('#edit_email').val(student.email || '');
            $('#edit_group_id').val(student.group_id || '');
            $('#editModal').fadeIn();
        }
        
        function closeEditModal() {
            $('#editModal').fadeOut();
        }
        
        $('#searchStudent').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#studentsTable tbody tr').each(function() {
                const name = $(this).data('student-name');
                $(this).toggle(name.includes(searchTerm));
            });
        });
    </script>
</body>
</html>
