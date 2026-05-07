<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\RestaurantRepository;
use App\Repositories\ReviewRepository;
use App\Services\ReviewService;

class ReviewApiController extends Controller
{
    public function list(string $restaurantId): void
    {
        $resolvedRestaurantId = filter_var($restaurantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($resolvedRestaurantId === false) {
            $this->json(['error' => 'Invalid restaurant id.'], 422);
            return;
        }

        $service = $this->reviewService();
        $this->json([
            'reviews' => $service->getRestaurantReviews($resolvedRestaurantId, $this->resolveLocale()),
            'stats' => $service->getRestaurantReviewStats($resolvedRestaurantId),
        ]);
    }

    public function create(string $restaurantId): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $resolvedRestaurantId = filter_var($restaurantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($resolvedRestaurantId === false) {
            $this->json([
                'error' => 'Invalid restaurant id.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $foodId = filter_var($payload['food_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $result = $this->reviewService()->createReview(
            (int) $this->session->userId(),
            $resolvedRestaurantId,
            $foodId === false ? null : $foodId,
            (int) ($payload['rating'] ?? 0),
            trim((string) ($payload['comment'] ?? ''))
        );

        $status = (int) ($result['status'] ?? 200);
        $response = [
            'message' => $result['message'] ?? '',
            'csrf_token' => $this->csrf->getToken(),
        ];

        if (!empty($result['success'])) {
            $response['review'] = $result['review'] ?? null;
            $this->json($response, $status);
            return;
        }

        if (isset($result['errors'])) {
            $response['errors'] = $result['errors'];
        }

        $response['error'] = $result['message'] ?? 'Unable to create review.';
        $this->json($response, $status);
    }

    public function update(string $reviewId): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $resolvedReviewId = filter_var($reviewId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($resolvedReviewId === false) {
            $this->json([
                'error' => 'Invalid review id.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $result = $this->reviewService()->updateReview(
            $resolvedReviewId,
            (int) $this->session->userId(),
            (int) ($payload['rating'] ?? 0),
            trim((string) ($payload['comment'] ?? ''))
        );

        $status = (int) ($result['status'] ?? 200);
        $response = [
            'message' => $result['message'] ?? '',
            'csrf_token' => $this->csrf->getToken(),
        ];

        if (!empty($result['success'])) {
            $response['review'] = $result['review'] ?? null;
            $this->json($response, $status);
            return;
        }

        if (isset($result['errors'])) {
            $response['errors'] = $result['errors'];
        }

        $response['error'] = $result['message'] ?? 'Unable to update review.';
        $this->json($response, $status);
    }

    public function delete(string $reviewId): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $resolvedReviewId = filter_var($reviewId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($resolvedReviewId === false) {
            $this->json([
                'error' => 'Invalid review id.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $result = $this->reviewService()->deleteReview($resolvedReviewId, (int) $this->session->userId());
        $status = (int) ($result['status'] ?? 200);
        $response = [
            'message' => $result['message'] ?? '',
            'csrf_token' => $this->csrf->getToken(),
        ];

        if (!empty($result['success'])) {
            $this->json($response, $status);
            return;
        }

        $response['error'] = $result['message'] ?? 'Unable to delete review.';
        $this->json($response, $status);
    }

    private function reviewService(): ReviewService
    {
        return new ReviewService(
            $this->app,
            new ReviewRepository($this->db),
            new RestaurantRepository($this->db)
        );
    }

    private function ensureAuthenticated(): bool
    {
        if ($this->session->isLoggedIn()) {
            return true;
        }

        $this->json(['error' => 'Authentication required.'], 401);
        return false;
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

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
