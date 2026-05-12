<?php
function send_course_mail(string $replyEmail, string $replyName, string $subject, string $body): array
{
    $config = require __DIR__ . "/mail_config.php";
    $autoload = dirname(__DIR__) . "/vendor/autoload.php";

    if (!file_exists($autoload)) {
        return [
            "ok" => false,
            "debug" => "PHPMailer is configured in code, but vendor/autoload.php was not found. Run: composer require phpmailer/phpmailer"
        ];
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
        $mail->addAddress($config["to_email"], $config["to_name"]);
        $mail->addReplyTo($replyEmail, $replyName);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return ["ok" => true, "debug" => "Email sent successfully with PHPMailer SMTP."];
    } catch (Throwable $e) {
        return ["ok" => false, "debug" => $e->getMessage()];
    }
}
?>
