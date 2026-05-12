<?php
session_start();
require __DIR__ . "/php/connect.php";

$errors = [];
$success = "";
$values = [
    "full_name" => "",
    "student_email" => "",
    "student_id" => "",
    "service_type" => "",
    "academic_year" => "",
    "contact_method" => "",
    "message" => ""
];
$selectedInterests = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $values["full_name"] = trim((string) filter_input(INPUT_POST, "full_name", FILTER_SANITIZE_SPECIAL_CHARS));
    $values["student_email"] = trim((string) filter_input(INPUT_POST, "student_email", FILTER_SANITIZE_EMAIL));
    $values["student_id"] = trim((string) filter_input(INPUT_POST, "student_id", FILTER_SANITIZE_SPECIAL_CHARS));
    $values["service_type"] = trim((string) filter_input(INPUT_POST, "service_type", FILTER_SANITIZE_SPECIAL_CHARS));
    $values["academic_year"] = trim((string) filter_input(INPUT_POST, "academic_year", FILTER_SANITIZE_SPECIAL_CHARS));
    $values["contact_method"] = trim((string) filter_input(INPUT_POST, "contact_method", FILTER_SANITIZE_SPECIAL_CHARS));
    $values["message"] = trim((string) filter_input(INPUT_POST, "message", FILTER_SANITIZE_SPECIAL_CHARS));
    $selectedInterests = filter_input(INPUT_POST, "interests", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
    $selectedInterests = array_map(fn($item) => htmlspecialchars(trim($item), ENT_QUOTES, "UTF-8"), $selectedInterests);

    if ($values["full_name"] === "") { $errors[] = "Full name is required."; }
    if (!filter_var($values["student_email"], FILTER_VALIDATE_EMAIL)) { $errors[] = "A valid email address is required."; }
    if ($values["student_id"] === "") { $errors[] = "Student ID is required."; }
    if ($values["service_type"] === "") { $errors[] = "Please choose a service type."; }
    if ($values["academic_year"] === "") { $errors[] = "Please choose an academic year."; }
    if ($values["contact_method"] === "") { $errors[] = "Please choose a contact method."; }
    if (count($selectedInterests) === 0) { $errors[] = "Please choose at least one interest."; }
    if ($values["message"] === "") { $errors[] = "Message is required."; }
    if (!isset($_POST["agree"])) { $errors[] = "Please confirm the declaration checkbox."; }

    if (!$errors) {
        $interests = implode(",", $selectedInterests);
        $sql = "INSERT INTO service_requests (full_name, student_email, student_id, service_type, academic_year, contact_method, interests, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            "ssssssss",
            $values["full_name"],
            $values["student_email"],
            $values["student_id"],
            $values["service_type"],
            $values["academic_year"],
            $values["contact_method"],
            $interests,
            $values["message"]
        );
        $stmt->execute();
        $success = "Your form data was validated and inserted with a prepared statement. New record ID: " . $stmt->insert_id;
        foreach ($values as $key => $value) { $values[$key] = ""; }
        $selectedInterests = [];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scenario A - Form and Database</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<header>
    <h1>User Registration Form</h1>
    <p>CISC3003 Final Exam - Scenario A</p>
</header>
<main>
    <?php if ($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($errors): ?>
        <div class="message error"><strong>Please fix:</strong><br><?= implode("<br>", array_map("htmlspecialchars", $errors)) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php" data-validate novalidate>
        <label for="full_name">Full Name</label>
        <input id="full_name" name="full_name" type="text" placeholder="Enter your full name" required maxlength="120" value="<?= htmlspecialchars($values["full_name"]) ?>">

        <label for="student_email">Student Email</label>
        <input id="student_email" name="student_email" type="email" placeholder="Enter your email address" required maxlength="160" value="<?= htmlspecialchars($values["student_email"]) ?>">

        <label for="student_id">Student ID</label>
        <input id="student_id" name="student_id" type="text" placeholder="Enter your student ID" required maxlength="40" value="<?= htmlspecialchars($values["student_id"]) ?>">

        <label for="service_type">Service Type</label>
        <select id="service_type" name="service_type" required>
            <option value="">Choose one</option>
            <?php foreach (["Academic Advising", "Technical Support", "Database Lab Help", "Exam Consultation"] as $option): ?>
                <option value="<?= $option ?>" <?= $values["service_type"] === $option ? "selected" : "" ?>><?= $option ?></option>
            <?php endforeach; ?>
        </select>

        <label>Academic Year</label>
        <?php foreach (["Year 1", "Year 2", "Year 3", "Year 4"] as $year): ?>
            <label class="choice"><input type="radio" name="academic_year" value="<?= $year ?>" <?= $values["academic_year"] === $year ? "checked" : "" ?> required> <?= $year ?></label>
        <?php endforeach; ?>

        <label>Areas of Interest</label>
        <?php foreach (["HTML", "CSS", "PHP", "MySQL"] as $interest): ?>
            <label class="choice"><input type="checkbox" name="interests[]" value="<?= $interest ?>" <?= in_array($interest, $selectedInterests, true) ? "checked" : "" ?>> <?= $interest ?></label>
        <?php endforeach; ?>

        <label for="contact_method">Preferred Contact Method</label>
        <select id="contact_method" name="contact_method" required>
            <option value="">Choose one</option>
            <?php foreach (["Email", "Phone", "Microsoft Teams"] as $method): ?>
                <option value="<?= $method ?>" <?= $values["contact_method"] === $method ? "selected" : "" ?>><?= $method ?></option>
            <?php endforeach; ?>
        </select>

        <label for="message">Message</label>
        <textarea id="message" name="message" placeholder="Write your message here..." required><?= htmlspecialchars($values["message"]) ?></textarea>

        <label class="choice"><input type="checkbox" name="agree" value="1" required> I confirm the submitted data is correct.</label>
        <div class="actions"><button type="submit">Submit</button></div>
    </form>

    </main>
<footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer>
</body>
</html>


