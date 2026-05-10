<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\HistoryRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\ReviewRepository;
use App\Services\RestaurantService;

class RestaurantController extends Controller
{
    public function detail(string $id): void
    {
        $restaurantId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($restaurantId === false) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return;
        }

        $locale = $this->resolveLocale();
        $restaurant = $this->restaurantService()->getRestaurantById($restaurantId, $locale);

        if ($restaurant === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return;
        }

        $isLoggedIn = $this->session->isLoggedIn();
        $currentUserId = $isLoggedIn ? (int) $this->session->userId() : 0;

        if ($isLoggedIn) {
            (new HistoryRepository($this->db))->logRestaurantBrowse($currentUserId, $restaurantId);
        }

        $favoriteLookup = $isLoggedIn
            ? array_fill_keys((new FavoriteRepository($this->db))->getFavoriteIds($currentUserId), true)
            : [];
        $restaurant['foods'] = array_map(static function (array $food) use ($favoriteLookup): array {
            $food['is_favorited'] = isset($favoriteLookup[(int) ($food['id'] ?? 0)]);

            return $food;
        }, is_array($restaurant['foods'] ?? null) ? $restaurant['foods'] : []);

        $hasReviewed = false;

        foreach ($restaurant['reviews'] ?? [] as $review) {
            if ((int) ($review['user_id'] ?? 0) === $currentUserId) {
                $hasReviewed = true;
                break;
            }
        }

        $this->view('restaurants/detail', [
            'title' => ($restaurant['name'] ?? 'Restaurant') . ' · Taste of Macau',
            'app' => $this->app,
            'locale' => $locale,
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-app',
            'restaurant' => $restaurant,
            'currentUserId' => $currentUserId,
            'isLoggedIn' => $isLoggedIn,
            'hasReviewed' => $hasReviewed,
            'canWriteReview' => $isLoggedIn && !$hasReviewed,
        ]);
    }

    private function restaurantService(): RestaurantService
    {
        return new RestaurantService(
            new RestaurantRepository($this->db),
            new ReviewRepository($this->db)
        );
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
