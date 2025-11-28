<?php
require_once __DIR__ . '/config.php';

function getDBConnection() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        $logFile = __DIR__ . '/../logs/db_errors.log';
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $errorMessage = date('Y-m-d H:i:s') . " - Connection failed: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $errorMessage, FILE_APPEND);
        throw new Exception("Database connection failed. Please try again later.");
    }
}

function testConnection() {
    try {
        $pdo = getDBConnection();
        return "Connection successful";
    } catch (Exception $e) {
        return "Connection failed: " . $e->getMessage();
    }
}
