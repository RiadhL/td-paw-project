<?php
require_once __DIR__ . '/db_connect.php';

function initializeDatabase() {
    try {
        $pdo = getDBConnection();
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL CHECK (role IN ('student', 'professor', 'admin')),
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS students (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            student_id VARCHAR(20) UNIQUE NOT NULL,
            matricule VARCHAR(20) UNIQUE NOT NULL,
            fullname VARCHAR(100) NOT NULL,
            group_id INTEGER,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS professors (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            fullname VARCHAR(100) NOT NULL,
            department VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS courses (
            id SERIAL PRIMARY KEY,
            course_name VARCHAR(100) NOT NULL,
            course_code VARCHAR(20) UNIQUE NOT NULL,
            professor_id INTEGER REFERENCES professors(id),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS groups (
            id SERIAL PRIMARY KEY,
            group_name VARCHAR(50) NOT NULL,
            course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS student_courses (
            id SERIAL PRIMARY KEY,
            student_id INTEGER REFERENCES students(id) ON DELETE CASCADE,
            course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
            group_id INTEGER REFERENCES groups(id),
            UNIQUE(student_id, course_id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_sessions (
            id SERIAL PRIMARY KEY,
            course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
            group_id INTEGER REFERENCES groups(id),
            session_date DATE NOT NULL,
            session_number INTEGER DEFAULT 1,
            opened_by INTEGER REFERENCES professors(id),
            status VARCHAR(20) DEFAULT 'open' CHECK (status IN ('open', 'closed')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_records (
            id SERIAL PRIMARY KEY,
            session_id INTEGER REFERENCES attendance_sessions(id) ON DELETE CASCADE,
            student_id INTEGER REFERENCES students(id) ON DELETE CASCADE,
            status VARCHAR(20) DEFAULT 'absent' CHECK (status IN ('present', 'absent')),
            participation BOOLEAN DEFAULT FALSE,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(session_id, student_id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS justifications (
            id SERIAL PRIMARY KEY,
            student_id INTEGER REFERENCES students(id) ON DELETE CASCADE,
            session_id INTEGER REFERENCES attendance_sessions(id) ON DELETE CASCADE,
            reason TEXT NOT NULL,
            file_path VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP,
            reviewed_by INTEGER REFERENCES users(id)
        )");

        insertSampleData($pdo);
        
        return "Database initialized successfully";
    } catch (Exception $e) {
        return "Database initialization failed: " . $e->getMessage();
    }
}

function insertSampleData($pdo) {
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM users");
    if ($checkUsers->fetchColumn() > 0) {
        return;
    }

    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $profPass = password_hash('prof123', PASSWORD_DEFAULT);
    $studentPass = password_hash('student123', PASSWORD_DEFAULT);

    $pdo->exec("INSERT INTO users (username, password, role, fullname, email) VALUES
        ('admin', '$adminPass', 'admin', 'System Administrator', 'admin@univ-alger.dz'),
        ('prof_benali', '$profPass', 'professor', 'Dr. Benali Ahmed', 'benali@univ-alger.dz'),
        ('prof_meziane', '$profPass', 'professor', 'Dr. Meziane Fatima', 'meziane@univ-alger.dz'),
        ('ahmed_sara', '$studentPass', 'student', 'Ahmed Sara', 'ahmed.sara@univ-alger.dz'),
        ('yacine_ali', '$studentPass', 'student', 'Yacine Ali', 'yacine.ali@univ-alger.dz'),
        ('houcine_rania', '$studentPass', 'student', 'Houcine Rania', 'houcine.rania@univ-alger.dz'),
        ('boudiaf_karim', '$studentPass', 'student', 'Boudiaf Karim', 'boudiaf.karim@univ-alger.dz'),
        ('mansouri_lina', '$studentPass', 'student', 'Mansouri Lina', 'mansouri.lina@univ-alger.dz')
    ");

    $pdo->exec("INSERT INTO professors (user_id, fullname, department) VALUES
        (2, 'Dr. Benali Ahmed', 'Computer Science'),
        (3, 'Dr. Meziane Fatima', 'Computer Science')
    ");

    $pdo->exec("INSERT INTO students (user_id, student_id, matricule, fullname, group_id, email) VALUES
        (4, '101', 'MAT001', 'Ahmed Sara', 1, 'ahmed.sara@univ-alger.dz'),
        (5, '102', 'MAT002', 'Yacine Ali', 1, 'yacine.ali@univ-alger.dz'),
        (6, '103', 'MAT003', 'Houcine Rania', 1, 'houcine.rania@univ-alger.dz'),
        (7, '104', 'MAT004', 'Boudiaf Karim', 2, 'boudiaf.karim@univ-alger.dz'),
        (8, '105', 'MAT005', 'Mansouri Lina', 2, 'mansouri.lina@univ-alger.dz')
    ");

    $pdo->exec("INSERT INTO courses (course_name, course_code, professor_id) VALUES
        ('Advanced Web Programming', 'AWP101', 1),
        ('Database Systems', 'DBS201', 1),
        ('Software Engineering', 'SE301', 2)
    ");

    $pdo->exec("INSERT INTO groups (group_name, course_id) VALUES
        ('Group A', 1),
        ('Group B', 1),
        ('Group A', 2),
        ('Group A', 3)
    ");

    $pdo->exec("INSERT INTO student_courses (student_id, course_id, group_id) VALUES
        (1, 1, 1), (1, 2, 3),
        (2, 1, 1), (2, 2, 3),
        (3, 1, 1), (3, 3, 4),
        (4, 1, 2), (4, 3, 4),
        (5, 1, 2), (5, 2, 3)
    ");

    for ($session = 1; $session <= 6; $session++) {
        $date = date('Y-m-d', strtotime("-" . (7 - $session) . " days"));
        $pdo->exec("INSERT INTO attendance_sessions (course_id, group_id, session_date, session_number, opened_by, status) VALUES
            (1, 1, '$date', $session, 1, 'closed')
        ");
    }

    $attendanceData = [
        [1, 1, 'absent', false], [1, 2, 'present', false], [1, 3, 'present', true],
        [2, 1, 'present', false], [2, 2, 'absent', false], [2, 3, 'absent', false],
        [3, 1, 'present', true], [3, 2, 'present', true], [3, 3, 'present', true],
        [4, 1, 'absent', false], [4, 2, 'present', true], [4, 3, 'present', true],
        [5, 1, 'present', true], [5, 2, 'present', true], [5, 3, 'absent', false],
        [6, 1, 'present', true], [6, 2, 'present', false], [6, 3, 'present', false],
    ];

    foreach ($attendanceData as $record) {
        $participation = $record[3] ? 'TRUE' : 'FALSE';
        $pdo->exec("INSERT INTO attendance_records (session_id, student_id, status, participation) VALUES
            ({$record[0]}, {$record[1]}, '{$record[2]}', $participation)
        ");
    }
}

if (php_sapi_name() === 'cli' || (isset($_GET['init']) && $_GET['init'] === 'true')) {
    echo initializeDatabase();
}
