<?php
$config = [
    'host' => '127.0.0.1',
    'dbname' => 'u9621710_worklog',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

// Create a mysqli connection and expose it as $conn for legacy code
$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);
if ($conn->connect_errno) {
    error_log('Database connection failed: ' . $conn->connect_error);
    // Halt with minimal message to avoid leaking credentials
    die('Database connection failed.');
}
$conn->set_charset($config['charset'] ?? 'utf8mb4');

// Export config in case other code expects it
return $config;
