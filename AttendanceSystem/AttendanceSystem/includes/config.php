<?php
$db_host = getenv('PGHOST') ?: 'localhost';
$db_port = getenv('PGPORT') ?: '5432';
$db_name = getenv('PGDATABASE') ?: 'attendance_db';
$db_user = getenv('PGUSER') ?: 'root';
$db_password = getenv('PGPASSWORD') ?: '';

define('DB_HOST', $db_host);
define('DB_PORT', $db_port);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASSWORD', $db_password);

define('UPLOAD_PATH', __DIR__ . '/../public/uploads/justifications/');

date_default_timezone_set('Africa/Algiers');
