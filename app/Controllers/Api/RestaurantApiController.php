<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\RestaurantRepository;
use App\Repositories\ReviewRepository;
use App\Services\RestaurantService;

class RestaurantApiController extends Controller
{
    public function list(): void
    {
        $locale = $this->resolveLocale();
        $area = trim((string) $this->request->get('area', ''));
        $service = $this->restaurantService();

        if ($area !== '') {
            $this->json($service->getRestaurantsByArea($area, $locale));
            return;
        }

        $this->json($service->getAllRestaurants($locale));
    }

    public function detail(string $id): void
    {
        $restaurantId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($restaurantId === false) {
            $this->json(['error' => 'Invalid restaurant id.'], 422);
            return;
        }

        $restaurant = $this->restaurantService()->getRestaurantById($restaurantId, $this->resolveLocale());

        if ($restaurant === null) {
            $this->json(['error' => 'Restaurant not found.'], 404);
            return;
        }

        $this->json($restaurant);
    }

    public function search(): void
    {
        $query = trim((string) $this->request->get('q', ''));

        if ($query === '') {
            $this->json(['error' => 'Search query is required.'], 422);
            return;
        }

        $this->json($this->restaurantService()->searchRestaurants($query, $this->resolveLocale()));
    }

    public function topRated(): void
    {
        $limit = filter_var($this->request->get('limit', 10), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 10;
        $this->json($this->restaurantService()->getTopRated($limit, $this->resolveLocale()));
    }

    public function nearby(): void
    {
        $lat = filter_var($this->request->get('lat'), FILTER_VALIDATE_FLOAT);
        $lng = filter_var($this->request->get('lng'), FILTER_VALIDATE_FLOAT);
        $radius = filter_var($this->request->get('radius', 2), FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lng === false) {
            $this->json(['error' => 'Valid lat and lng parameters are required.'], 422);
            return;
        }

        $this->json(
            $this->restaurantService()->getNearby(
                (float) $lat,
                (float) $lng,
                $this->resolveLocale(),
                $radius !== false && $radius > 0 ? (float) $radius : 2.0
            )
        );
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
