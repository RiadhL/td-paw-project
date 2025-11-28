<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: /admin/index.php');
    } elseif ($role === 'professor') {
        header('Location: /professor/index.php');
    } else {
        header('Location: /student/index.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($username, $password);
    
    if ($result['success']) {
        if ($result['role'] === 'admin') {
            header('Location: /admin/index.php');
        } elseif ($result['role'] === 'professor') {
            header('Location: /professor/index.php');
        } else {
            header('Location: /student/index.php');
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Algiers University Attendance System</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Algiers University</h1>
                <h2>Attendance Management System</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                    <span class="error-message" id="username-error"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="error-message" id="password-error"></span>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-info">
                <h4>Demo Accounts:</h4>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Professor:</strong> prof_benali / prof123</p>
                <p><strong>Student:</strong> ahmed_sara / student123</p>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/validation.js"></script>
</body>
</html>
