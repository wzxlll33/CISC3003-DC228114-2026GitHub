<?php
$status = $_GET["status"] ?? "";
$debug = $_GET["debug"] ?? "";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scenario B - Contact Form with PHPMailer</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<header>
    <h1>Contact Us</h1>
    <p>CISC3003 Final Exam - Scenario B</p>
</header>
<main>
    <?php if ($status === "sent"): ?><div class="message success">The contact message was processed and the page used PRG to avoid duplicate submission.</div><?php endif; ?>
    <?php if ($status === "failed"): ?><div class="message warning">The message was saved, but email sending needs SMTP setup. Debug: <?= htmlspecialchars($debug) ?></div><?php endif; ?>
    <form method="post" action="php/send_contact.php" data-validate>
        <label for="name">Name</label>
        <input id="name" name="name" placeholder="Enter your full name" required maxlength="120">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="Enter your email address" required maxlength="160">
        <label for="subject">Subject</label>
        <input id="subject" name="subject" placeholder="Enter the subject" required maxlength="160">
        <label for="message">Message</label>
        <textarea id="message" name="message" placeholder="Write your message here..." required minlength="10"></textarea>
        <div class="actions"><button type="submit">Send Message</button></div>
    </form>
    </main>
<footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer>
</body>
</html>

