<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile:$errline");
    return true;
});
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}
function getDatabase() {
    static $conn = null;
    
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        loadEnv(__DIR__ . '/../../.env');
        $servername = $_ENV['DB_SERVERNAME'] ?? 'localhost';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        $dbname = $_ENV['DB_NAME'] ?? 'company_attendance';
        
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        die("Database error. Please try again later.");
    }
}
function initializeDatabase() {
    try {
        $conn = getDatabase();
        
        $dbname = $_ENV['DB_NAME'] ?? 'company_attendance';
        
        $sql = "CREATE DATABASE IF NOT EXISTS " . $conn->real_escape_string($dbname);
        if (!$conn->query($sql)) {
            throw new Exception("Failed to create database: " . $conn->error);
        }
        

        if (!$conn->select_db($dbname)) {
            throw new Exception("Failed to select database: " . $conn->error);
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS testovaqi_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            date DATE NOT NULL,
            time_in TIME NOT NULL,
            time_out TIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_entry (employee_id, date, time_in)
        )";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to create table: " . $conn->error);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
        throw $e;
    }
}

try {
    initializeDatabase();
} catch (Exception $e) {
    error_log("Failed to initialize database: " . $e->getMessage());
}
