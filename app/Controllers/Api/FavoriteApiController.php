<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\FoodRepository;
use App\Services\FoodService;

class FavoriteApiController extends Controller
{
    public function toggle(string $foodId): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $resolvedFoodId = filter_var($foodId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($resolvedFoodId === false) {
            $this->json([
                'error' => 'Invalid food id.',
                'csrf_token' => $this->csrf->getToken(),
            ], 422);
            return;
        }

        $foodRepository = new FoodRepository($this->db);
        $food = $foodRepository->getById($resolvedFoodId);

        if ($food === null) {
            $this->json([
                'error' => 'Food not found.',
                'csrf_token' => $this->csrf->getToken(),
            ], 404);
            return;
        }

        $result = (new FavoriteRepository($this->db))->toggle((int) $this->session->userId(), $resolvedFoodId);

        $this->json([
            'action' => $result['action'] ?? 'added',
            'food_id' => $resolvedFoodId,
            'csrf_token' => $this->csrf->getToken(),
        ]);
    }

    public function list(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $locale = $this->resolveLocale();
        $foodService = new FoodService(new FoodRepository($this->db));
        $favorites = (new FavoriteRepository($this->db))->getUserFavorites((int) $this->session->userId());

        $formatted = array_map(function (array $favorite) use ($foodService, $locale): array {
            $food = $foodService->formatFood($favorite, $locale);
            $food['is_favorited'] = true;
            $food['favorited_at'] = (string) ($favorite['favorited_at'] ?? '');

            return $food;
        }, $favorites);

        $this->json($formatted);
    }

    public function sync(): void
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $payload = $this->jsonBody();

        if (!$this->validateCsrfPayload($payload)) {
            return;
        }

        $foodIds = $this->normalizeFoodIds($payload['food_ids'] ?? []);
        $favoriteRepository = new FavoriteRepository($this->db);
        $foodRepository = new FoodRepository($this->db);
        $synced = 0;

        foreach ($foodIds as $foodId) {
            if ($foodRepository->getById($foodId) === null) {
                continue;
            }

            if ($favoriteRepository->add((int) $this->session->userId(), $foodId)) {
                $synced++;
            }
        }

        $this->json([
            'synced' => $synced,
            'favorite_ids' => $favoriteRepository->getFavoriteIds((int) $this->session->userId()),
            'csrf_token' => $this->csrf->getToken(),
        ]);
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

    private function normalizeFoodIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $foodId) {
            $resolvedFoodId = filter_var($foodId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($resolvedFoodId === false) {
                continue;
            }

            $ids[] = (int) $resolvedFoodId;
        }

        return array_values(array_unique(array_slice($ids, 0, 200)));
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
