<?php
/*
 * Database connection for both local XAMPP and Railway deployment.
 * Local default: localhost / root / empty password.
 * Railway: values are read automatically from the MySQL service variables.
 */
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = (int) (getenv('MYSQLPORT') ?: 3306);
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'cisc3003_paper02a';

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

$mysqli->query("CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    student_email VARCHAR(160) NOT NULL,
    student_id VARCHAR(40) NOT NULL,
    service_type VARCHAR(80) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    contact_method VARCHAR(30) NOT NULL,
    interests VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$mysqli->query("CREATE TABLE IF NOT EXISTS demo_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>