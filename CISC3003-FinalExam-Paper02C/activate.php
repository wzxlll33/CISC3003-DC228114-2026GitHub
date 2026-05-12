<?php
require __DIR__ . "/php/connect.php";
$message = "Invalid activation token.";
$token = $_GET["token"] ?? "";
if ($token !== "") {
    $hash = hash("sha256", $token);
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE account_activation_hash = ?");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $stmt = $mysqli->prepare("UPDATE users SET account_activation_hash = NULL WHERE id = ?");
        $stmt->bind_param("i", $user["id"]);
        $stmt->execute();
        $message = "Account activated. You can now log in.";
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Activate Account</title><link rel="stylesheet" href="css/styles.css"></head><body><header><h1>Account Activation</h1><p>CISC3003 Final Exam - Scenario C</p></header><main><section class="panel"><p><?= htmlspecialchars($message) ?></p></section></main><footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer></body></html>

