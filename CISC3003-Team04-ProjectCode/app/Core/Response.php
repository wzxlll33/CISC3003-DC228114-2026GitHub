<?php

namespace App\Core;

class Response
{
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function redirect(string $url, int $status = 302): void
    {
        header('Location: ' . $url, true, $status);
    }

    public function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }
}
