<?php
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - Algiers University</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="error-container">
        <h1>403 - Unauthorized</h1>
        <p>You do not have permission to access this page.</p>
        <a href="/login.php" class="btn btn-primary">Return to Login</a>
    </div>
</body>
</html>
