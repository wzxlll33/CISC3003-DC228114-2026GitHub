<?php

use App\Controllers\Api\FavoriteApiController;
use App\Controllers\Api\FeedbackApiController;
use App\Controllers\Api\FoodApiController;
use App\Controllers\Api\HistoryApiController;
use App\Controllers\Api\LocaleApiController;
use App\Controllers\Api\MapApiController;
use App\Controllers\Api\RestaurantApiController;
use App\Controllers\Api\ReviewApiController;
use App\Controllers\HealthController;

$router->group('/api', function ($router): void {
    $registerCustomMethod = \Closure::bind(
        function (string $method, string $path, string $handler): void {
            $this->addRoute($method, $path, $handler);
        },
        $router,
        get_class($router)
    );

    $router->get('/health', HealthController::class . '@index');
    $router->get('/foods', FoodApiController::class . '@list');
    $router->get('/foods/search', FoodApiController::class . '@search');
    $router->get('/foods/{id}', FoodApiController::class . '@detail');
    $router->get('/restaurants', RestaurantApiController::class . '@list');
    $router->get('/restaurants/search', RestaurantApiController::class . '@search');
    $router->get('/restaurants/top', RestaurantApiController::class . '@topRated');
    $router->get('/restaurants/nearby', RestaurantApiController::class . '@nearby');
    $router->get('/restaurants/{id}/reviews', ReviewApiController::class . '@list');
    $router->post('/restaurants/{id}/reviews', ReviewApiController::class . '@create');
    $router->get('/restaurants/{id}', RestaurantApiController::class . '@detail');
    $router->get('/favorites', FavoriteApiController::class . '@list');
    $router->post('/favorites/sync', FavoriteApiController::class . '@sync');
    $router->post('/favorites/{foodId}', FavoriteApiController::class . '@toggle');
    $router->get('/map/markers', MapApiController::class . '@markers');
    $router->get('/map/geocode', MapApiController::class . '@geocode');
    $router->post('/history/browse', HistoryApiController::class . '@logBrowse');
    $router->post('/history/search', HistoryApiController::class . '@logSearch');
    $router->get('/history/search', HistoryApiController::class . '@getSearch');
    $router->get('/history/browse', HistoryApiController::class . '@getBrowse');
    $router->post('/feedback', FeedbackApiController::class . '@create');
    $router->post('/locale', LocaleApiController::class . '@update');
    $registerCustomMethod('PUT', '/reviews/{id}', ReviewApiController::class . '@update');
    $registerCustomMethod('DELETE', '/reviews/{id}', ReviewApiController::class . '@delete');
});
