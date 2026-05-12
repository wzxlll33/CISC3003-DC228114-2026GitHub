<?php
require __DIR__ . "/connect.php";
require __DIR__ . "/mailer.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$name = trim((string) filter_input(INPUT_POST, "name", FILTER_SANITIZE_SPECIAL_CHARS));
$email = trim((string) filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL));
$subject = trim((string) filter_input(INPUT_POST, "subject", FILTER_SANITIZE_SPECIAL_CHARS));
$message = trim((string) filter_input(INPUT_POST, "message", FILTER_SANITIZE_SPECIAL_CHARS));

if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === "" || strlen($message) < 10) {
    header("Location: ../index.php?status=failed&debug=" . urlencode("Server-side validation failed."));
    exit;
}

$result = send_course_mail($email, $name, $subject, $message);
$mailStatus = $result["ok"] ? "sent" : "failed";
$debug = $result["debug"];

$stmt = $mysqli->prepare("INSERT INTO contact_messages (name, email, subject, message, mail_status, debug_message) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $subject, $message, $mailStatus, $debug);
$stmt->execute();

header("Location: ../index.php?status=" . urlencode($mailStatus) . "&debug=" . urlencode($debug));
exit;
?>
