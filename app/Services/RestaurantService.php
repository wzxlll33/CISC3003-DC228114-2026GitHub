<?php

namespace App\Services;

use App\Repositories\RestaurantRepository;
use App\Repositories\ReviewRepository;

class RestaurantService
{
    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly ReviewRepository $reviewRepository
    ) {
    }

    public function getAllRestaurants(string $locale): array
    {
        return array_map(
            fn (array $restaurant): array => $this->formatRestaurant($restaurant, $locale),
            $this->restaurantRepository->getAll()
        );
    }

    public function getRestaurantById(int $id, string $locale): array|null
    {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return null;
        }

        $formatted = $this->formatRestaurant($restaurant, $locale);
        $formatted['foods'] = array_map(
            fn (array $food): array => $this->formatFood($food, $locale),
            $restaurant['foods'] ?? []
        );
        $formatted['reviews'] = array_map(
            fn (array $review): array => $this->formatReview($review, $locale),
            $this->reviewRepository->getByRestaurant($id)
        );
        $formatted['rating_stats'] = $this->reviewRepository->getStats($id);

        return $formatted;
    }

    public function searchRestaurants(string $query, string $locale): array
    {
        return array_map(
            fn (array $restaurant): array => $this->formatRestaurant($restaurant, $locale),
            $this->restaurantRepository->search($query)
        );
    }

    public function getTopRated(int $limit, string $locale): array
    {
        return array_map(
            fn (array $restaurant): array => $this->formatRestaurant($restaurant, $locale),
            $this->restaurantRepository->getTopRated($limit)
        );
    }

    public function getNearby(float $lat, float $lng, string $locale, float $radiusKm = 2): array
    {
        return array_map(function (array $restaurant) use ($locale): array {
            $formatted = $this->formatRestaurant($restaurant, $locale);
            $formatted['distance_km'] = isset($restaurant['distance_km']) ? (float) $restaurant['distance_km'] : null;

            return $formatted;
        }, $this->restaurantRepository->getNearby($lat, $lng, $radiusKm));
    }

    public function getRestaurantsByArea(string $area, string $locale): array
    {
        return array_map(
            fn (array $restaurant): array => $this->formatRestaurant($restaurant, $locale),
            $this->restaurantRepository->getByArea($area)
        );
    }

    public function formatRestaurant(array $restaurant, string $locale): array
    {
        $resolvedLocale = $this->resolveLocale($locale);

        return [
            'id' => (int) ($restaurant['id'] ?? 0),
            'name' => $this->resolveLocalizedValue($restaurant, 'name', $resolvedLocale),
            'description' => $this->resolveLocalizedValue($restaurant, 'description', $resolvedLocale),
            'address' => $this->resolveLocalizedValue($restaurant, 'address', $resolvedLocale),
            'phone' => (string) ($restaurant['phone'] ?? ''),
            'opening_hours' => (string) ($restaurant['opening_hours'] ?? ''),
            'price_range' => (string) ($restaurant['price_range'] ?? ''),
            'latitude' => isset($restaurant['latitude']) ? (float) $restaurant['latitude'] : null,
            'longitude' => isset($restaurant['longitude']) ? (float) $restaurant['longitude'] : null,
            'area' => $this->resolveLocalizedValue($restaurant, 'area', $resolvedLocale),
            'image_url' => (string) ($restaurant['image_url'] ?? ''),
            'avg_rating' => isset($restaurant['avg_rating']) ? (float) $restaurant['avg_rating'] : 0.0,
            'google_rating' => isset($restaurant['google_rating']) ? (float) $restaurant['google_rating'] : null,
            'amap_rating' => isset($restaurant['amap_rating']) ? (float) $restaurant['amap_rating'] : null,
            'review_count' => (int) ($restaurant['review_count'] ?? 0),
            'tags' => array_map(
                fn (array $tag): array => [
                    'id' => (int) ($tag['id'] ?? 0),
                    'slug' => (string) ($tag['slug'] ?? ''),
                    'name' => $this->resolveLocalizedValue($tag, 'name', $resolvedLocale),
                ],
                $restaurant['tags'] ?? []
            ),
        ];
    }

    private function formatFood(array $food, string $locale): array
    {
        $resolvedLocale = $this->resolveLocale($locale);

        return [
            'id' => (int) ($food['id'] ?? 0),
            'name' => $this->resolveLocalizedValue($food, 'name', $resolvedLocale),
            'description' => $this->resolveLocalizedValue($food, 'description', $resolvedLocale),
            'image_url' => (string) ($food['image_url'] ?? ''),
            'area' => $this->resolveLocalizedValue($food, 'area', $resolvedLocale),
            'price_range' => (string) ($food['price_range'] ?? ''),
            'rating' => isset($food['rating']) ? (float) $food['rating'] : 0.0,
            'category_name' => $this->resolveLocalizedValue($food, 'category_name', $resolvedLocale),
            'category_slug' => (string) ($food['category_slug'] ?? ''),
            'category_icon' => (string) ($food['category_icon'] ?? ''),
            'is_signature' => !empty($food['is_signature']),
        ];
    }

    private function formatReview(array $review, string $locale): array
    {
        $resolvedLocale = $this->resolveLocale($locale);

        return [
            'id' => (int) ($review['id'] ?? 0),
            'user_id' => (int) ($review['user_id'] ?? 0),
            'username' => (string) ($review['username'] ?? ''),
            'avatar_url' => (string) ($review['avatar_url'] ?? ''),
            'rating' => (int) ($review['rating'] ?? 0),
            'comment' => (string) ($review['comment'] ?? ''),
            'food_id' => isset($review['food_id']) ? (int) $review['food_id'] : null,
            'food_name' => $review['food_id'] !== null
                ? $this->resolveLocalizedValue($review, 'food_name', $resolvedLocale)
                : null,
            'created_at' => (string) ($review['created_at'] ?? ''),
            'updated_at' => (string) ($review['updated_at'] ?? ''),
        ];
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
