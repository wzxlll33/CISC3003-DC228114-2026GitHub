<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\UserRepository;

class LocaleApiController extends Controller
{
    public function update(): void
    {
        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $locale = trim((string) ($payload['locale'] ?? ''));

        if (!in_array($locale, ['en', 'zh', 'pt'], true)) {
            $this->json([
                'error' => 'Invalid locale.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $this->session->set('locale', $locale);

        if ($this->session->isLoggedIn()) {
            (new UserRepository($this->db))->update((int) $this->session->userId(), ['locale' => $locale]);
        }

        $this->json([
            'locale' => $locale,
            'csrf_token' => $this->csrf->getToken(),
        ]);
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function validateCsrfPayload(array $payload): bool
    {
        $token = isset($payload['_token']) ? (string) $payload['_token'] : '';

        if ($this->csrf->validateToken($token)) {
            return true;
        }

        $this->json([
            'error' => 'Invalid CSRF token.',
            'csrf_token' => $this->csrf->getToken(),
        ], 419);

        return false;
    }
}
