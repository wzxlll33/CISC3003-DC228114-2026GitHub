<?php
header("Content-Type: application/json");
require __DIR__ . "/connect.php";
$email = trim($_GET["email"] ?? "");
$available = false;
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $available = $stmt->get_result()->num_rows === 0;
}
echo json_encode(["available" => $available]);
?>
