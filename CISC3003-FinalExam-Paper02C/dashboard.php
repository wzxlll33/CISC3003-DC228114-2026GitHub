<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Scenario C Dashboard</title><link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/dashboard.css"></head><body>
<header><h1>User Dashboard</h1><p>Welcome, <?= htmlspecialchars($_SESSION["full_name"]) ?>. You became a user on <?= htmlspecialchars($_SESSION["created_at"]) ?>.</p><nav><a href="index.php">Home</a><a href="logout.php" class="secondary">Logout</a></nav></header>
<main class="dashboard-shell"><section class="service-grid"><article class="service-card"><h3>Profile</h3><p>Email: <?= htmlspecialchars($_SESSION["email"]) ?></p></article><article class="service-card"><h3>Course Services</h3><p>Access PHP, MySQL, and web programming resources.</p></article><article class="service-card"><h3>Security</h3><p>Password reset and email confirmation are enabled.</p></article><article class="service-card"><h3>Support</h3><p>Contact the site administrator for service requests.</p></article></section></main>
<footer><p>CISC3003 Web Programming: Kris Wu Zexian + DC228114 + 2026</p></footer></body></html>
