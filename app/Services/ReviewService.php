<?php

namespace App\Services;

use App\Core\App;
use App\Repositories\RestaurantRepository;
use App\Repositories\ReviewRepository;

class ReviewService
{
    public function __construct(
        private readonly App $app,
        private readonly ReviewRepository $reviewRepository,
        private readonly RestaurantRepository $restaurantRepository
    ) {
    }

    public function createReview(int $userId, int $restaurantId, int|null $foodId, int $rating, string $comment): array
    {
        $validation = $this->validateReviewPayload($rating, $comment);

        if (!$validation['valid']) {
            return $validation;
        }

        $restaurant = $this->restaurantRepository->getById($restaurantId);

        if ($restaurant === null) {
            return ['success' => false, 'message' => 'Restaurant not found.', 'status' => 404];
        }

        if ($this->reviewRepository->hasUserReviewed($userId, $restaurantId)) {
            return ['success' => false, 'message' => 'You have already reviewed this restaurant.', 'status' => 409];
        }

        if ($foodId !== null && !$this->restaurantServesFood($restaurant, $foodId)) {
            return ['success' => false, 'message' => 'Selected food is not served by this restaurant.', 'status' => 422];
        }

        $review = $this->reviewRepository->create($userId, $restaurantId, $foodId, $rating, trim($comment));

        return [
            'success' => true,
            'message' => 'Review created successfully.',
            'review' => $review,
            'status' => 201,
        ];
    }

    public function updateReview(int $reviewId, int $userId, int $rating, string $comment): array
    {
        $validation = $this->validateReviewPayload($rating, $comment);

        if (!$validation['valid']) {
            return $validation;
        }

        $review = $this->reviewRepository->findById($reviewId);

        if ($review === null) {
            return ['success' => false, 'message' => 'Review not found.', 'status' => 404];
        }

        if ((int) ($review['user_id'] ?? 0) !== $userId) {
            return ['success' => false, 'message' => 'You can only update your own review.', 'status' => 403];
        }

        $updated = $this->reviewRepository->update($reviewId, $userId, $rating, trim($comment));

        return [
            'success' => true,
            'message' => 'Review updated successfully.',
            'review' => $updated,
            'status' => 200,
        ];
    }

    public function deleteReview(int $reviewId, int $userId): array
    {
        $review = $this->reviewRepository->findById($reviewId);

        if ($review === null) {
            return ['success' => false, 'message' => 'Review not found.', 'status' => 404];
        }

        if ((int) ($review['user_id'] ?? 0) !== $userId) {
            return ['success' => false, 'message' => 'You can only delete your own review.', 'status' => 403];
        }

        $deleted = $this->reviewRepository->delete($reviewId, $userId);

        if (!$deleted) {
            return ['success' => false, 'message' => 'Unable to delete review.', 'status' => 500];
        }

        return [
            'success' => true,
            'message' => 'Review deleted successfully.',
            'status' => 200,
        ];
    }

    public function getRestaurantReviews(int $restaurantId, string $locale = 'zh'): array
    {
        return array_map(
            fn (array $review): array => $this->formatReview($review, $locale),
            $this->reviewRepository->getByRestaurant($restaurantId)
        );
    }

    public function getUserReviews(int $userId, string $locale = 'zh'): array
    {
        return array_map(
            fn (array $review): array => $this->formatReview($review, $locale, true),
            $this->reviewRepository->getByUser($userId)
        );
    }

    public function getRestaurantReviewStats(int $restaurantId): array
    {
        return $this->reviewRepository->getStats($restaurantId);
    }

    private function validateReviewPayload(int $rating, string $comment): array
    {
        $payload = [
            'rating' => (string) $rating,
            'comment' => trim($comment),
        ];
        $validation = $this->app->validator()->validate($payload, [
            'rating' => 'required|min:1|max:1',
            'comment' => 'required|min:2|max:1000',
        ]);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validation['errors'],
                'status' => 422,
                'valid' => false,
            ];
        }

        if ($rating < 1 || $rating > 5) {
            return [
                'success' => false,
                'message' => 'Rating must be between 1 and 5.',
                'status' => 422,
                'valid' => false,
            ];
        }

        return ['valid' => true];
    }

    private function restaurantServesFood(array $restaurant, int $foodId): bool
    {
        foreach (($restaurant['foods'] ?? []) as $food) {
            if ((int) ($food['id'] ?? 0) === $foodId) {
                return true;
            }
        }

        return false;
    }

    private function formatReview(array $review, string $locale, bool $includeRestaurant = false): array
    {
        $resolvedLocale = $this->resolveLocale($locale);
        $formatted = [
            'id' => (int) ($review['id'] ?? 0),
            'user_id' => (int) ($review['user_id'] ?? 0),
            'restaurant_id' => (int) ($review['restaurant_id'] ?? 0),
            'food_id' => isset($review['food_id']) ? (int) $review['food_id'] : null,
            'username' => (string) ($review['username'] ?? ''),
            'avatar_url' => (string) ($review['avatar_url'] ?? ''),
            'rating' => (int) ($review['rating'] ?? 0),
            'comment' => (string) ($review['comment'] ?? ''),
            'food_name' => isset($review['food_id']) && $review['food_id'] !== null
                ? $this->resolveLocalizedValue($review, 'food_name', $resolvedLocale)
                : null,
            'created_at' => (string) ($review['created_at'] ?? ''),
            'updated_at' => (string) ($review['updated_at'] ?? ''),
        ];

        if ($includeRestaurant) {
            $formatted['restaurant_name'] = $this->resolveLocalizedValue($review, 'restaurant_name', $resolvedLocale);
        }

        return $formatted;
    }

    private function resolveLocale(string $locale): string
    {
        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }

    private function resolveLocalizedValue(array $row, string $prefix, string $locale): string
    {
        $preferredKey = $prefix . '_' . $locale;
        $fallbackKeys = [$preferredKey, $prefix . '_zh', $prefix . '_en', $prefix . '_pt', $prefix];

        foreach ($fallbackKeys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return '';
    }
}
