<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection settings
$host = 'localhost';
$db   = 'hotel_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Debug function - log to file
// Only declare if not already defined to prevent redeclaration error
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        $log_file = __DIR__ . '/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message";
        
        if ($data !== null) {
            $log_message .= ": " . print_r($data, true);
        }
        
        file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
    }
}
?>