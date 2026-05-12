<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "cisc3003_paper02a";

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
