<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\FoodRepository;
use App\Repositories\HistoryRepository;
use App\Services\FoodService;

class HistoryApiController extends Controller
{
    public function logSearch(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $query = trim((string) ($payload['query'] ?? ''));

        if ($query === '') {
            $this->json([
                'error' => 'A search query is required.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $filtersJson = $filters === []
            ? null
            : json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $resultsCount = filter_var($payload['results_count'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $resultsCount = $resultsCount === false ? 0 : (int) $resultsCount;
        $history = new HistoryRepository($this->db);
        $logged = false;

        if (!$history->hasRecentSearch((int) $this->session->userId(), $query, $filtersJson)) {
            $history->logSearch((int) $this->session->userId(), $query, $filtersJson, $resultsCount);
            $logged = true;
        }

        $this->json([
            'message' => $logged ? 'Search history logged.' : 'Recent search already recorded.',
            'logged' => $logged,
            'csrf_token' => $this->csrf->getToken(),
        ]);
    }

    public function logBrowse(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $foodId = filter_var($payload['food_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($foodId === false) {
            $this->json([
                'error' => 'A valid food_id is required.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $food = (new FoodRepository($this->db))->getById($foodId);

        if ($food === null) {
            $this->json([
                'error' => 'Food not found.',
                'csrf_token' => $this->csrf->getToken(),
            ], 404);
            return;
        }

        $logged = (new HistoryRepository($this->db))->logBrowse((int) $this->session->userId(), $foodId);

        $this->json([
            'message' => $logged ? 'Browse history logged.' : 'Recent browse already recorded.',
            'logged' => $logged,
            'csrf_token' => $this->csrf->getToken(),
        ]);
    }

    public function getSearch(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $history = (new HistoryRepository($this->db))->getSearchHistory((int) $this->session->userId());
        $formatted = array_map(static function (array $entry): array {
            return [
                'id' => (int) ($entry['id'] ?? 0),
                'query' => (string) ($entry['query'] ?? ''),
                'filters' => self::decodeFilters($entry['filters_json'] ?? null),
                'results_count' => (int) ($entry['results_count'] ?? 0),
                'created_at' => (string) ($entry['created_at'] ?? ''),
            ];
        }, $history);

        $this->json($formatted);
    }

    public function getBrowse(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $locale = $this->resolveLocale();
        $foodService = new FoodService(new FoodRepository($this->db));
        $history = (new HistoryRepository($this->db))->getBrowseHistory((int) $this->session->userId());
        $formatted = array_map(function (array $entry) use ($foodService, $locale): array {
            if (($entry['item_type'] ?? 'food') === 'restaurant') {
                $restaurant = $this->formatBrowseRestaurant($entry, $locale);

                return [
                    'id' => (int) ($entry['id'] ?? 0),
                    'type' => 'restaurant',
                    'created_at' => (string) ($entry['created_at'] ?? ''),
                    'item' => $restaurant,
                    'restaurant' => $restaurant,
                ];
            }

            $food = $foodService->formatFood($entry, $locale);

            return [
                'id' => (int) ($entry['id'] ?? 0),
                'type' => 'food',
                'created_at' => (string) ($entry['created_at'] ?? ''),
                'item' => $food,
                'food' => $food,
            ];
        }, $history);

        $this->json($formatted);
    }

    private function formatBrowseRestaurant(array $entry, string $locale): array
    {
        $resolvedLocale = in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';

        return [
            'id' => (int) ($entry['restaurant_id'] ?? 0),
            'name' => $this->localizedValue($entry, 'name', $resolvedLocale),
            'description' => $this->localizedValue($entry, 'description', $resolvedLocale),
            'image_url' => (string) ($entry['image_url'] ?? ''),
            'area' => $this->localizedValue($entry, 'area', $resolvedLocale),
            'price_range' => (string) ($entry['price_range'] ?? ''),
            'rating' => isset($entry['avg_rating']) ? (float) $entry['avg_rating'] : (float) ($entry['rating'] ?? 0),
            'category_name' => $this->viewEngine->t('restaurantDetail.restaurant', $resolvedLocale),
            'detail_url' => '/restaurant/' . (int) ($entry['restaurant_id'] ?? 0),
        ];
    }

    private function localizedValue(array $row, string $prefix, string $locale): string
    {
        foreach ([$prefix . '_' . $locale, $prefix . '_zh', $prefix . '_en', $prefix . '_pt', $prefix] as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return '';
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

    private static function decodeFilters(mixed $filtersJson): array
    {
        if (!is_string($filtersJson) || trim($filtersJson) === '') {
            return [];
        }

        $decoded = json_decode($filtersJson, true);

        return is_array($decoded) ? $decoded : [];
    }
}
