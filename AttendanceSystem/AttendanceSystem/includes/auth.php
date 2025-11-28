<?php
session_start();

require_once __DIR__ . '/db_connect.php';

function login($username, $password) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];

            if ($user['role'] === 'student') {
                $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $student = $stmt->fetch();
                if ($student) {
                    $_SESSION['student_id'] = $student['id'];
                }
            } elseif ($user['role'] === 'professor') {
                $stmt = $pdo->prepare("SELECT id FROM professors WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $professor = $stmt->fetch();
                if ($professor) {
                    $_SESSION['professor_id'] = $professor['id'];
                }
            }

            return ['success' => true, 'role' => $user['role']];
        }
        return ['success' => false, 'message' => 'Invalid username or password'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

function logout() {
    session_unset();
    session_destroy();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: /unauthorized.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'fullname' => $_SESSION['fullname'],
        'student_id' => $_SESSION['student_id'] ?? null,
        'professor_id' => $_SESSION['professor_id'] ?? null
    ];
}
