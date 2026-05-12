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
$database = getenv('MYSQLDATABASE') ?: '{cisc3003_paper02b}';

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
?>
