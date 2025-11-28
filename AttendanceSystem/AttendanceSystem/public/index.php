<?php
require_once __DIR__ . '/../includes/init_db.php';

initializeDatabase();

header('Location: /login.php');
exit;
