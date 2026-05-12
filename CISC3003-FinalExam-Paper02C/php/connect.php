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
$database = getenv('MYSQLDATABASE') ?: 'cisc3003_paper02c';

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

$mysqli->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    account_activation_hash CHAR(64) DEFAULT NULL,
    reset_token_hash CHAR(64) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (account_activation_hash),
    INDEX (reset_token_hash)
)");
?>