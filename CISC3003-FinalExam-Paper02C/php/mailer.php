<?php
function send_course_mail(string $toEmail, string $toName, string $subject, string $body): array
{
    $config = require __DIR__ . "/mail_config.php";
    if (empty($config["username"]) || empty($config["password"])) {
        return ["ok" => false, "debug" => "SMTP credentials are not configured. Use the classroom test link shown on this page."];
    }

    $autoload = dirname(__DIR__) . "/vendor/autoload.php";
    if (!file_exists($autoload)) {
        return ["ok" => false, "debug" => "PHPMailer vendor/autoload.php not found. Run composer require phpmailer/phpmailer, then set SMTP credentials in php/mail_config.php."];
    }
    require $autoload;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = "UTF-8";
        $mail->isSMTP();
        $mail->Host = $config["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $config["username"];
        $mail->Password = $config["password"];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config["port"];
        $mail->Timeout = 10;
        $mail->SMTPDebug = $config["debug"];
        $mail->setFrom($config["from_email"], $config["from_name"]);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return ["ok" => true, "debug" => "Email sent."];
    } catch (Throwable $e) {
        return ["ok" => false, "debug" => $e->getMessage()];
    }
}
?>
