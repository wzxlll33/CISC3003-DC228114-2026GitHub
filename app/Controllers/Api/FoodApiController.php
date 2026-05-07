<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\FoodRepository;
use App\Repositories\HistoryRepository;
use App\Services\FoodService;
use App\Services\SearchService;

class FoodApiController extends Controller
{
    public function list(): void
    {
        $locale = $this->resolveLocale();
        $categorySlug = trim((string) $this->request->get('category', ''));
        $foodService = $this->foodService();

        if ($categorySlug !== '') {
            $this->json($this->withFavoriteState($foodService->getFoodsByCategory($categorySlug, $locale)));
            return;
        }

        $this->json($this->withFavoriteState($foodService->getAllFoods($locale)));
    }

    public function detail(string $id): void
    {
        $foodId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($foodId === false) {
            $this->json(['error' => 'Invalid food id.'], 422);
            return;
        }

        $food = $this->foodService()->getFoodById($foodId, $this->resolveLocale());

        if ($food === null) {
            $this->json(['error' => 'Food not found.'], 404);
            return;
        }

        $this->json($this->withFavoriteStateForFood($food));
    }

    public function search(): void
    {
        $locale = $this->resolveLocale();
        $query = trim((string) $this->request->get('q', ''));
        $categorySlug = trim((string) $this->request->get('category', ''));
        $searchService = new SearchService($this->foodService(), new HistoryRepository($this->db));
        $results = $searchService->search(
            $query,
            $categorySlug !== '' ? $categorySlug : null,
            $this->session->isLoggedIn() ? (int) $this->session->userId() : null,
            $locale
        );

        $this->json($this->withFavoriteState($results));
    }

    private function foodService(): FoodService
    {
        return new FoodService(new FoodRepository($this->db));
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }

    private function withFavoriteState(array $foods): array
    {
        if (!$this->session->isLoggedIn()) {
            return array_map(static function (array $food): array {
                $food['is_favorited'] = false;

                return $food;
            }, $foods);
        }

        $favoriteLookup = array_fill_keys(
            (new FavoriteRepository($this->db))->getFavoriteIds((int) $this->session->userId()),
            true
        );

        return array_map(static function (array $food) use ($favoriteLookup): array {
            $food['is_favorited'] = isset($favoriteLookup[(int) ($food['id'] ?? 0)]);

            return $food;
        }, $foods);
    }

    private function withFavoriteStateForFood(array $food): array
    {
        $foods = $this->withFavoriteState([$food]);

        return $foods[0] ?? $food;
    }
}
