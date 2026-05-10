<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\FoodRepository;
use App\Repositories\HistoryRepository;
use App\Services\FoodService;

class FoodController extends Controller
{
    public function detail(string $id): void
    {
        $foodId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($foodId === false) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        $locale = $this->resolveLocale();
        $foodService = new FoodService(new FoodRepository($this->db));
        $food = $foodService->getFoodById($foodId, $locale);

        if ($food === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        if ($this->session->isLoggedIn()) {
            $historyRepository = new HistoryRepository($this->db);
            $historyRepository->logBrowse((int) $this->session->userId(), $foodId);
            $food['is_favorited'] = (new FavoriteRepository($this->db))->isFavorited((int) $this->session->userId(), $foodId);
        } else {
            $food['is_favorited'] = false;
        }

        $this->view('foods/detail', [
            'title' => $food['name'] . ' · Taste of Macau',
            'app' => $this->app,
            'locale' => $locale,
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-app',
            'food' => $food,
        ]);
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
