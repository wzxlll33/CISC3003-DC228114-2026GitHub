<?php
return [
    "host" => getenv("MAIL_HOST") ?: "smtp.gmail.com",
    "port" => (int) (getenv("MAIL_PORT") ?: 587),
    "username" => getenv("MAIL_USERNAME") ?: "wzx20041108@gmail.com",
    "password" => getenv("MAIL_PASSWORD") ?: (getenv("GMAIL_APP_PASSWORD") ?: ""),
    "from_email" => getenv("MAIL_FROM_EMAIL") ?: "wzx20041108@gmail.com",
    "from_name" => getenv("MAIL_FROM_NAME") ?: "CISC3003 Paper02C",
    "debug" => (int) (getenv("MAIL_DEBUG") ?: 0)
];
?>
