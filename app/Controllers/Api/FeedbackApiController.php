<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\FeedbackRepository;
use App\Repositories\FoodRepository;
use App\Repositories\RestaurantRepository;
use Throwable;

class FeedbackApiController extends Controller
{
    private const ISSUE_TYPES = ['missing_store', 'wrong_address', 'closed', 'wrong_info', 'other'];
    private const CONTEXT_TYPES = ['general', 'restaurant', 'food', 'explore'];

    public function create(): void
    {
        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $issueType = trim((string) ($payload['issue_type'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $contextType = trim((string) ($payload['context_type'] ?? 'general'));
        $contactEmail = strtolower(trim((string) ($payload['contact_email'] ?? '')));

        $errors = [];

        if (!in_array($issueType, self::ISSUE_TYPES, true)) {
            $errors['issue_type'][] = 'Please select a valid issue type.';
        }

        if (mb_strlen($message) < 10) {
            $errors['message'][] = 'Please enter at least 10 characters.';
        }

        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'][] = 'Please enter a valid email address.';
        }

        $contextType = in_array($contextType, self::CONTEXT_TYPES, true) ? $contextType : 'general';
        $restaurantId = $this->optionalId($payload['restaurant_id'] ?? null);
        $foodId = $this->optionalId($payload['food_id'] ?? null);

        if ($restaurantId !== null && (new RestaurantRepository($this->db))->getById($restaurantId) === null) {
            $errors['restaurant_id'][] = 'Restaurant not found.';
        }

        if ($foodId !== null && (new FoodRepository($this->db))->getById($foodId) === null) {
            $errors['food_id'][] = 'Food not found.';
        }

        if ($errors !== []) {
            $this->json([
                'error' => 'Please fix the highlighted fields.',
                'errors' => $errors,
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        try {
            $id = (new FeedbackRepository($this->db))->create([
                'user_id' => $this->session->isLoggedIn() ? (int) $this->session->userId() : null,
                'restaurant_id' => $restaurantId,
                'food_id' => $foodId,
                'context_type' => $contextType,
                'issue_type' => $issueType,
                'message' => $message,
                'contact_email' => $contactEmail !== '' ? $contactEmail : null,
                'page_url' => $this->truncate((string) ($payload['page_url'] ?? ''), 500),
                'user_agent' => $this->truncate((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 500),
            ]);
        } catch (Throwable) {
            $this->json([
                'error' => 'Unable to submit feedback.',
                'csrf_token' => $this->csrf->getToken(),
            ], 500);
            return;
        }

        $this->json([
            'message' => 'Feedback submitted.',
            'id' => $id,
            'csrf_token' => $this->csrf->getToken(),
        ], 201);
    }

    private function optionalId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : (int) $id;
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

    private function truncate(string $value, int $maxLength): string
    {
        return mb_strlen($value) > $maxLength ? mb_substr($value, 0, $maxLength) : $value;
    }
}
