<?php
require __DIR__ . "/php/connect.php";
require __DIR__ . "/php/mailer.php";
$notice = "";
$resetLink = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
    if ($email) {
        $token = bin2hex(random_bytes(16));
        $hash = hash("sha256", $token);
        $expires = date("Y-m-d H:i:s", time() + 1800);
        $stmt = $mysqli->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?");
        $stmt->bind_param("sss", $hash, $expires, $email);
        $stmt->execute();
        $resetLink = "http://localhost/CISC3003-FinalExam-Paper02C/reset_password.php?token=" . urlencode($token);
        $mail = send_course_mail($email, "CISC3003 User", "Password reset", "Reset your password: " . $resetLink);
        $notice = "If the email exists, a reset link has been prepared. Mail status: " . $mail["debug"];
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Forgot Password</title><link rel="stylesheet" href="css/styles.css"></head><body><header><h1>Password Reset</h1><p>CISC3003 Final Exam - Scenario C</p></header><main><form method="post"><?php if ($notice): ?><div class="message"><?= htmlspecialchars($notice) ?><?php if ($resetLink): ?><br><a href="<?= htmlspecialchars($resetLink) ?>">Classroom test reset link</a><?php endif; ?></div><?php endif; ?><label>Email</label><input type="email" name="email" placeholder="Enter your email address" required><div class="actions"><button>Send Reset Link</button></div></form></main><footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer></body></html>

