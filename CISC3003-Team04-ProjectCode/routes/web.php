<?php

use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\DashboardController;
use App\Controllers\FoodController;
use App\Controllers\HomeController;
use App\Controllers\RestaurantController;

// Public
$router->get('/', HomeController::class . '@landing');

// Auth
$router->get('/login', AuthController::class . '@loginForm');
$router->post('/login', AuthController::class . '@login');
$router->get('/register', AuthController::class . '@registerForm');
$router->post('/register', AuthController::class . '@register');
$router->get('/verify', AuthController::class . '@verify');
$router->get('/forgot-password', AuthController::class . '@forgotForm');
$router->post('/forgot-password', AuthController::class . '@forgot');
$router->get('/reset-password', AuthController::class . '@resetForm');
$router->post('/reset-password', AuthController::class . '@reset');
$router->post('/logout', AuthController::class . '@logout');

// Public browsing
$router->get('/explore', HomeController::class . '@index');
$router->get('/food/{id}', FoodController::class . '@detail');
$router->get('/restaurant/{id}', RestaurantController::class . '@detail');

// Admin
$router->get('/admin', AdminController::class . '@index');
$router->get('/admin/foods', AdminController::class . '@foods');
$router->get('/admin/restaurants', AdminController::class . '@restaurants');
$router->get('/admin/restaurants/create', AdminController::class . '@createRestaurant');
$router->post('/admin/restaurants', AdminController::class . '@storeRestaurant');
$router->get('/admin/restaurants/{restaurantId}/foods/create', AdminController::class . '@createRestaurantFood');
$router->post('/admin/restaurants/{restaurantId}/foods', AdminController::class . '@storeRestaurantFood');
$router->get('/admin/restaurants/{restaurantId}/foods/{foodId}/edit', AdminController::class . '@editRestaurantFood');
$router->post('/admin/restaurants/{restaurantId}/foods/{foodId}', AdminController::class . '@updateRestaurantFood');
$router->post('/admin/restaurants/{restaurantId}/foods/{foodId}/remove', AdminController::class . '@removeRestaurantFood');
$router->get('/admin/restaurants/{id}/edit', AdminController::class . '@editRestaurant');
$router->post('/admin/restaurants/{id}', AdminController::class . '@updateRestaurant');
$router->post('/admin/restaurants/{id}/delete', AdminController::class . '@deleteRestaurant');
$router->get('/admin/feedback', AdminController::class . '@feedback');
$router->post('/admin/feedback/{id}/status', AdminController::class . '@updateFeedbackStatus');

// Auth-gated dashboard
$router->get('/dashboard', DashboardController::class . '@index');
$router->get('/dashboard/profile', DashboardController::class . '@profile');
$router->post('/dashboard/profile', DashboardController::class . '@updateProfile');
$router->get('/dashboard/search-history', DashboardController::class . '@searchHistory');
$router->get('/dashboard/browse-history', DashboardController::class . '@browseHistory');
$router->get('/dashboard/favorites', DashboardController::class . '@favorites');
$router->post('/dashboard/clear-search-history', DashboardController::class . '@clearSearchHistory');
$router->post('/dashboard/clear-browse-history', DashboardController::class . '@clearBrowseHistory');
