<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\FoodRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\ReviewRepository;
use App\Services\FoodService;
use App\Services\RestaurantService;

class HomeController extends Controller
{
    public function landing(): void
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/explore');
            return;
        }

        $locale = $this->resolveLocale();

        $this->view('home/landing', [
            'title' => 'Discover Macau\'s Culinary Heritage',
            'app' => $this->app,
            'locale' => $locale,
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-landing',
        ]);
    }

    public function index(): void
    {
        $locale = $this->resolveLocale();
        $foodService = new FoodService(new FoodRepository($this->db));
        $restaurantRepository = new RestaurantRepository($this->db);
        $restaurantService = new RestaurantService($restaurantRepository, new ReviewRepository($this->db));
        $favoriteIds = $this->session->isLoggedIn()
            ? (new FavoriteRepository($this->db))->getFavoriteIds((int) $this->session->userId())
            : [];
        $favoriteLookup = array_fill_keys($favoriteIds, true);
        $foods = array_map(function (array $food) use ($favoriteLookup, $restaurantRepository): array {
            $food['is_favorited'] = isset($favoriteLookup[(int) ($food['id'] ?? 0)]);
            $food['restaurant_ids'] = array_map(
                static fn (array $restaurant): int => (int) ($restaurant['id'] ?? 0),
                $restaurantRepository->getByFood((int) ($food['id'] ?? 0))
            );

            return $food;
        }, $foodService->getAllFoods($locale));
        $restaurants = $restaurantService->getAllRestaurants($locale);
        $categories = $foodService->getCategories($locale);
        $topRated = $restaurantService->getTopRated(10, $locale);

        $this->view('home/index', [
            'title' => 'Taste of Macau',
            'app' => $this->app,
            'locale' => $locale,
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-app',
            'restaurants' => $restaurants,
            'foods' => $foods,
            'topRated' => $topRated,
            'categories' => $categories,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
