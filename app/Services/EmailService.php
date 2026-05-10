<?php

namespace App\Services;

use App\Core\App;
use RuntimeException;
use Throwable;

class EmailService
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function sendVerificationEmail(string $email, string $username, string $token): void
    {
        $subject = '驗證你的 Taste of Macau 帳戶';
        $url = $this->baseUrl() . '/verify?token=' . urlencode($token);
        $html = $this->verificationHtml($username, $url);
        $text = implode(PHP_EOL, [
            '你好 ' . $username . '：',
            '',
            '請點擊以下連結驗證你的 Taste of Macau 帳戶：',
            $url,
            '',
            '此連結會在 24 小時後失效。',
        ]);

        $this->sendMail($email, $username, $subject, $html, $text, $url);
    }

    public function sendPasswordResetEmail(string $email, string $username, string $token): void
    {
        $subject = '重設你的 Taste of Macau 密碼';
        $url = $this->baseUrl() . '/reset-password?token=' . urlencode($token);
        $html = $this->actionHtml(
            $username,
            '重設密碼',
            '我們收到重設密碼的請求。請點擊下方按鈕設定新密碼。',
            '重設密碼',
            $url,
            '此連結會在 1 小時後失效。如非本人操作，請忽略此郵件。'
        );
        $text = implode(PHP_EOL, [
            '你好 ' . $username . '：',
            '',
            '請點擊以下連結重設你的 Taste of Macau 密碼：',
            $url,
            '',
            '此連結會在 1 小時後失效。如非本人操作，請忽略此郵件。',
        ]);

        $this->sendMail($email, $username, $subject, $html, $text, $url);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) $this->app->config('app.url', 'http://localhost:8000'), '/');
    }

    protected function sendMail(string $email, string $username, string $subject, string $html, string $text, string $url): void
    {
        $provider = strtolower((string) $this->app->config('mail.provider', 'log'));

        if ($provider === 'log') {
            $this->logMail($email, $username, $subject, $url);
            return;
        }

        try {
            if ($provider === 'cloudflare') {
                $this->sendViaCloudflare($email, $username, $subject, $html, $text);
                return;
            }

            if ($provider === 'smtp') {
                $this->sendViaSmtp($email, $username, $subject, $html, $text);
                return;
            }
        } catch (Throwable $throwable) {
            $this->logFailure($email, $subject, $throwable->getMessage());
            throw $throwable;
        }

        throw new RuntimeException('Unsupported mail provider: ' . $provider);
    }

    protected function sendViaCloudflare(string $email, string $username, string $subject, string $html, string $text): void
    {
        $accountId = trim((string) $this->app->config('mail.cloudflare.account_id', ''));
        $apiToken = trim((string) $this->app->config('mail.cloudflare.api_token', ''));
        $endpoint = trim((string) $this->app->config('mail.cloudflare.endpoint', ''));
        $timeout = max(1, (int) $this->app->config('mail.cloudflare.timeout', 10));
        $fromAddress = trim((string) $this->app->config('mail.from.address', 'no-reply@example.com'));
        $fromName = trim((string) $this->app->config('mail.from.name', 'Taste of Macau'));
        $replyToAddress = trim((string) $this->app->config('mail.reply_to.address', ''));

        if ($accountId === '' || $apiToken === '' || $endpoint === '') {
            throw new RuntimeException('Cloudflare email credentials are not configured.');
        }

        $endpoint = str_replace('{account_id}', rawurlencode($accountId), $endpoint);

        $payload = [
            'from' => [
                'address' => $fromAddress,
                'name' => $fromName,
            ],
            'to' => $email,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ];

        if ($replyToAddress !== '') {
            $payload['reply_to'] = $replyToAddress;
        }

        [$statusCode, $responseBody] = $this->postJson($endpoint, $apiToken, $payload, $timeout);
        $decoded = json_decode($responseBody, true);

        if ($statusCode < 200 || $statusCode >= 300 || !is_array($decoded) || ($decoded['success'] ?? false) !== true) {
            throw new RuntimeException($this->cloudflareErrorMessage($statusCode, is_array($decoded) ? $decoded : null, $responseBody));
        }

        $bounces = $decoded['result']['permanent_bounces'] ?? [];
        if (is_array($bounces) && $bounces !== []) {
            throw new RuntimeException('Cloudflare marked this recipient as a permanent bounce.');
        }
    }

    protected function sendViaSmtp(string $email, string $username, string $subject, string $html, string $text): void
    {
        $host = trim((string) $this->app->config('mail.smtp.host', ''));
        $port = max(1, (int) $this->app->config('mail.smtp.port', 465));
        $security = strtolower(trim((string) $this->app->config('mail.smtp.security', 'ssl')));
        $smtpUsername = trim((string) $this->app->config('mail.smtp.username', ''));
        $smtpPassword = (string) $this->app->config('mail.smtp.password', '');
        $timeout = max(1, (int) $this->app->config('mail.smtp.timeout', 20));
        $fromAddress = trim((string) $this->app->config('mail.from.address', 'no-reply@example.com'));
        $fromName = trim((string) $this->app->config('mail.from.name', 'Taste of Macau'));
        $replyToAddress = trim((string) $this->app->config('mail.reply_to.address', ''));

        if ($host === '' || $fromAddress === '') {
            throw new RuntimeException('SMTP host or sender address is not configured.');
        }

        if (($smtpUsername === '') !== ($smtpPassword === '')) {
            throw new RuntimeException('SMTP username and password must be configured together.');
        }

        $stream = $this->openSmtpStream($host, $port, $security, $timeout);

        try {
            $this->expectSmtp($stream, [220], 'SMTP banner');
            $this->smtpCommand($stream, 'EHLO taste-of-macau.local', [250], 'SMTP EHLO');

            if (in_array($security, ['starttls', 'tls'], true)) {
                $this->smtpCommand($stream, 'STARTTLS', [220], 'SMTP STARTTLS');

                if (stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    throw new RuntimeException('SMTP STARTTLS negotiation failed.');
                }

                $this->smtpCommand($stream, 'EHLO taste-of-macau.local', [250], 'SMTP EHLO after STARTTLS');
            }

            if ($smtpUsername !== '') {
                $this->smtpCommand($stream, 'AUTH LOGIN', [334], 'SMTP AUTH LOGIN');
                $this->smtpCommand($stream, base64_encode($smtpUsername), [334], 'SMTP AUTH username');
                $this->smtpCommand($stream, base64_encode($smtpPassword), [235], 'SMTP AUTH password');
            }

            $this->smtpCommand($stream, 'MAIL FROM:<' . $fromAddress . '>', [250], 'SMTP MAIL FROM');
            $this->smtpCommand($stream, 'RCPT TO:<' . $email . '>', [250, 251], 'SMTP RCPT TO');
            $this->smtpCommand($stream, 'DATA', [354], 'SMTP DATA');

            $message = $this->buildMimeMessage(
                $fromAddress,
                $fromName,
                $email,
                $username,
                $subject,
                $html,
                $text,
                $replyToAddress
            );

            fwrite($stream, $this->dotStuff($message) . "\r\n.\r\n");
            $this->expectSmtp($stream, [250], 'SMTP message body');
            $this->smtpCommand($stream, 'QUIT', [221], 'SMTP QUIT');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    protected function openSmtpStream(string $host, int $port, string $security, int $timeout): mixed
    {
        $scheme = in_array($security, ['ssl', 'smtps'], true) ? 'ssl' : 'tcp';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
                'SNI_enabled' => true,
            ],
        ]);

        $stream = @stream_socket_client(
            $scheme . '://' . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($stream)) {
            throw new RuntimeException('SMTP connection failed: ' . $errno . ' ' . $errstr);
        }

        stream_set_timeout($stream, $timeout);

        return $stream;
    }

    protected function smtpCommand(mixed $stream, string $command, array $expectedCodes, string $label): void
    {
        fwrite($stream, $command . "\r\n");
        $this->expectSmtp($stream, $expectedCodes, $label);
    }

    protected function expectSmtp(mixed $stream, array $expectedCodes, string $label): array
    {
        [$code, $lines] = $this->readSmtpResponse($stream);

        if (!in_array($code, $expectedCodes, true)) {
            $response = $lines === [] ? 'no response' : implode(' | ', $lines);
            throw new RuntimeException($label . ' failed with code ' . $code . ': ' . $response);
        }

        return [$code, $lines];
    }

    protected function readSmtpResponse(mixed $stream): array
    {
        $lines = [];

        while (($line = fgets($stream, 515)) !== false) {
            $line = rtrim($line, "\r\n");
            $lines[] = $line;

            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        $code = isset($lines[0]) ? (int) substr($lines[0], 0, 3) : 0;

        return [$code, $lines];
    }

    protected function buildMimeMessage(
        string $fromAddress,
        string $fromName,
        string $toAddress,
        string $toName,
        string $subject,
        string $html,
        string $text,
        string $replyToAddress
    ): string {
        $boundary = 'taste-of-macau-' . bin2hex(random_bytes(12));
        $messageId = '<' . bin2hex(random_bytes(16)) . '@' . $this->domainFromEmail($fromAddress) . '>';
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatMailbox($fromAddress, $fromName),
            'To: ' . $this->formatMailbox($toAddress, $toName),
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: ' . $messageId,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        if ($replyToAddress !== '') {
            $headers[] = 'Reply-To: ' . $replyToAddress;
        }

        $parts = [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($text), 76, "\r\n")),
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($html), 76, "\r\n")),
            '--' . $boundary . '--',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
    }

    protected function formatMailbox(string $address, string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '<' . $address . '>';
        }

        return $this->encodeHeader($name) . ' <' . $address . '>';
    }

    protected function encodeHeader(string $value): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $value) === 1) {
            return str_replace(["\r", "\n"], '', $value);
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    protected function domainFromEmail(string $email): string
    {
        $parts = explode('@', $email);

        return $parts[1] ?? 'localhost';
    }

    protected function dotStuff(string $message): string
    {
        return preg_replace('/^\./m', '..', $message) ?? $message;
    }

    protected function postJson(string $endpoint, string $apiToken, array $payload, int $timeout): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($json)) {
            throw new RuntimeException('Unable to encode mail payload.');
        }

        if (function_exists('curl_init')) {
            $handle = curl_init($endpoint);

            if ($handle === false) {
                throw new RuntimeException('Unable to initialize cURL.');
            }

            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_TIMEOUT => $timeout,
            ]);

            $responseBody = curl_exec($handle);
            $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $error = curl_error($handle);
            curl_close($handle);

            if ($responseBody === false) {
                throw new RuntimeException('Cloudflare request failed: ' . $error);
            }

            return [$statusCode, (string) $responseBody];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/json',
                ]),
                'content' => $json,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = file_get_contents($endpoint, false, $context);

        if ($responseBody === false) {
            throw new RuntimeException('Cloudflare request failed.');
        }

        $statusCode = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches) === 1) {
                $statusCode = (int) $matches[1];
                break;
            }
        }

        return [$statusCode, (string) $responseBody];
    }

    protected function cloudflareErrorMessage(int $statusCode, ?array $decoded, string $responseBody): string
    {
        $messages = [];

        foreach (($decoded['errors'] ?? []) as $error) {
            if (is_array($error) && isset($error['message'])) {
                $messages[] = (string) $error['message'];
            }
        }

        if ($messages === [] && $responseBody !== '') {
            $messages[] = substr($responseBody, 0, 300);
        }

        return 'Cloudflare email send failed with HTTP ' . $statusCode . ': ' . implode('; ', $messages);
    }

    protected function verificationHtml(string $username, string $url): string
    {
        return $this->actionHtml(
            $username,
            '驗證你的帳戶',
            '歡迎加入 Taste of Macau。請點擊下方按鈕完成郵箱驗證，之後就可以登入並使用收藏、評論等功能。',
            '驗證郵箱',
            $url,
            '此連結會在 24 小時後失效。如非本人註冊，請忽略此郵件。'
        );
    }

    protected function actionHtml(string $username, string $title, string $body, string $buttonText, string $url, string $footer): string
    {
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeBody = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $safeButtonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $safeFooter = htmlspecialchars($footer, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="zh-Hant">
<body style="margin:0;background:#f7f3eb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#241b16;">
  <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
    <div style="background:#fff;border:1px solid #eadfd0;border-radius:12px;padding:28px;">
      <p style="margin:0 0 8px;font-size:14px;color:#8a5a2f;">Taste of Macau</p>
      <h1 style="margin:0 0 18px;font-size:24px;line-height:1.25;">{$safeTitle}</h1>
      <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">你好 {$safeUsername}：</p>
      <p style="margin:0 0 24px;font-size:16px;line-height:1.7;">{$safeBody}</p>
      <p style="margin:0 0 24px;">
        <a href="{$safeUrl}" style="display:inline-block;background:#a7461f;color:#fff;text-decoration:none;border-radius:999px;padding:12px 20px;font-weight:700;">{$safeButtonText}</a>
      </p>
      <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#6d625b;">如果按鈕無法開啟，請複製以下連結到瀏覽器：<br>{$safeUrl}</p>
      <p style="margin:0;font-size:13px;line-height:1.6;color:#6d625b;">{$safeFooter}</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    protected function logMail(string $email, string $username, string $subject, string $url): void
    {
        $directory = ROOT_PATH . '/storage/logs';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $entry = sprintf(
            "[%s] Recipient: %s (%s)%sSubject: %s%sURL: %s%s%s",
            date('Y-m-d H:i:s'),
            $email,
            $username,
            PHP_EOL,
            $subject,
            PHP_EOL,
            $url,
            PHP_EOL,
            PHP_EOL
        );

        file_put_contents($directory . '/mail.log', $entry, FILE_APPEND);
    }

    protected function logFailure(string $email, string $subject, string $message): void
    {
        $directory = ROOT_PATH . '/storage/logs';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $entry = sprintf(
            "[%s] Recipient: %s%sSubject: %s%sError: %s%s%s",
            date('Y-m-d H:i:s'),
            $email,
            PHP_EOL,
            $subject,
            PHP_EOL,
            $message,
            PHP_EOL,
            PHP_EOL
        );

        file_put_contents($directory . '/mail-error.log', $entry, FILE_APPEND);
    }
}
