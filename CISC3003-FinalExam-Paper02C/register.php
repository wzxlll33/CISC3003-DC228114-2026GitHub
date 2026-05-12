<?php
require __DIR__ . "/php/connect.php";
require __DIR__ . "/php/mailer.php";
$errors = [];
$notice = "";
$activationLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim((string) filter_input(INPUT_POST, "full_name", FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim((string) filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL));
    $password = $_POST["password"] ?? "";
    $passwordConfirmation = $_POST["password_confirmation"] ?? "";

    if ($name === "") { $errors[] = "Name is required."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Valid email is required."; }
    if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters."; }
    if ($password !== $passwordConfirmation) { $errors[] = "Password confirmation must match."; }

    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { $errors[] = "Email is already registered."; }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $activationToken = bin2hex(random_bytes(16));
        $activationHash = hash("sha256", $activationToken);
        $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, password_hash, account_activation_hash) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $passwordHash, $activationHash);
        $stmt->execute();

        $activationLink = "http://localhost/CISC3003-FinalExam-Paper02C/activate.php?token=" . urlencode($activationToken);
        $mail = send_course_mail($email, $name, "Account activation", "Click this link to activate your account: " . $activationLink);
        $notice = "Account created. Please confirm your email before login. Mail status: " . $mail["debug"];
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Scenario C Register</title><link rel="stylesheet" href="css/styles.css"><script src="js/script.js" defer></script></head><body>
<header><h1>User Registration Form</h1><p>CISC3003 Final Exam - Scenario C</p></header>
<main><form method="post" data-validate><?php if ($errors): ?><div class="message error"><?= implode("<br>", array_map("htmlspecialchars", $errors)) ?></div><?php endif; ?><?php if ($notice): ?><div class="message success"><?= htmlspecialchars($notice) ?><?php if ($activationLink): ?><br><a href="<?= htmlspecialchars($activationLink) ?>">Classroom test activation link</a><?php endif; ?></div><?php endif; ?><label for="full_name">Full Name</label><input id="full_name" name="full_name" placeholder="Enter your full name" required><label for="email">Email</label><input id="email" data-check-url="php/check_email.php" type="email" name="email" placeholder="Enter your email address" required><p id="email-feedback" class="small"></p><label for="password">Password</label><input id="password" type="password" name="password" minlength="8" required><label for="password_confirmation">Confirm Password</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required><div class="actions"><button>Create Account</button></div></form></main>
<footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer></body></html>

