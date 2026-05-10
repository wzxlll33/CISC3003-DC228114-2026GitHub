<?php

namespace App\Core;

class Session
{
    protected bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            $this->ageFlash();
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $this->started = true;
        $this->ageFlash();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash']['next'][$key] = $value;
    }

    public function getFlash(string $key): mixed
    {
        $value = $_SESSION['_flash']['current'][$key] ?? null;
        unset($_SESSION['_flash']['current'][$key]);

        return $value;
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
        $this->started = false;
    }

    public function isLoggedIn(): bool
    {
        return $this->has('user_id');
    }

    public function userId(): mixed
    {
        return $this->get('user_id');
    }

    protected function ageFlash(): void
    {
        $_SESSION['_flash'] ??= ['current' => [], 'next' => []];
        $_SESSION['_flash']['current'] = $_SESSION['_flash']['next'] ?? [];
        $_SESSION['_flash']['next'] = [];
    }
}
