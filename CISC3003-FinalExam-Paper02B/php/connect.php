<?php
$mysqli = new mysqli("localhost", "root", "", "cisc3003_paper02b");
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
