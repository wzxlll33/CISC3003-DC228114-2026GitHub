<?php
function send_course_mail(string $toEmail, string $toName, string $subject, string $body): array
{
    $config = require __DIR__ . "/mail_config.php";
    $autoload = dirname(__DIR__) . "/vendor/autoload.php";
    if (!file_exists($autoload)) {
        return ["ok" => false, "debug" => "PHPMailer vendor/autoload.php not found. Run composer require phpmailer/phpmailer, then set SMTP credentials in php/mail_config.php."];
    }
    require $autoload;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $config["username"];
        $mail->Password = $config["password"];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config["port"];
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
