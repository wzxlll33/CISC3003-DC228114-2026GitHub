<?php
require __DIR__ . "/php/connect.php";
$token = $_GET["token"] ?? $_POST["token"] ?? "";
$hash = hash("sha256", $token);
$message = "";
$valid = false;
if ($token !== "") {
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $valid = (bool) $user;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && $valid) {
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["password_confirmation"] ?? "";
    if (strlen($password) >= 8 && $password === $confirm) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt->bind_param("si", $passwordHash, $user["id"]);
        $stmt->execute();
        $message = "Password updated. You can log in now.";
        $valid = false;
    } else {
        $message = "Password must be at least 8 characters and match confirmation.";
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset Password</title><link rel="stylesheet" href="css/styles.css"><script src="js/script.js" defer></script></head><body><header><h1>Reset Password</h1><nav><a href="login.php">Login</a></nav></header><main><?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($valid): ?><form method="post"><input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"><label>New Password</label><input id="password" type="password" name="password" minlength="8" required><label>Confirm Password</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required><div class="actions"><button>Update Password</button></div></form><?php else: ?><section class="panel"><p>The reset token is invalid, expired, or already used.</p></section><?php endif; ?></main><footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer></body></html>
