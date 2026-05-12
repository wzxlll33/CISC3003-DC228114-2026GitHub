<?php
session_start();
require __DIR__ . "/php/connect.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
    $password = $_POST["password"] ?? "";
    $stmt = $mysqli->prepare("SELECT id, full_name, email, password_hash, account_activation_hash, created_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && $user["account_activation_hash"] !== null) {
        $error = "Please confirm your email address before logging in.";
    } elseif ($user && password_verify($password, $user["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["created_at"] = $user["created_at"];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login.";
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Scenario C Login</title><link rel="stylesheet" href="css/styles.css"></head><body>
<header><h1>User Login</h1><p>CISC3003 Final Exam - Scenario C</p></header>
<main><form method="post"><?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?><label>Email</label><input type="email" name="email" placeholder="Enter your email address" required><label>Password</label><input type="password" name="password" required><div class="actions"><button>Login</button></div></form></main>
<footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer></body></html>

