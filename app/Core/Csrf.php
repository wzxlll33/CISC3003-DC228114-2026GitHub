<?php

namespace App\Core;

class Csrf
{
    protected Session $session;

    protected string $sessionKey = '_csrf_token';

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set($this->sessionKey, $token);

        return $token;
    }

    public function getToken(): string
    {
        $token = $this->session->get($this->sessionKey);

        if (!is_string($token) || $token === '') {
            $token = $this->generateToken();
        }

        return $token;
    }

    public function validateToken(?string $token): bool
    {
        $storedToken = $this->session->get($this->sessionKey);

        if (!is_string($storedToken) || !is_string($token) || $token === '') {
            return false;
        }

        $valid = hash_equals($storedToken, $token);

        if ($valid) {
            $this->session->remove($this->sessionKey);
        }

        return $valid;
    }

    public function tokenField(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}
