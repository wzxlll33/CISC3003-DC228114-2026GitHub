<?php

namespace App\Services;

use App\Repositories\FoodRepository;

class FoodService
{
    public function __construct(private readonly FoodRepository $foodRepository)
    {
    }

    public function getAllFoods(string $locale = 'zh'): array
    {
        return array_map(fn (array $food): array => $this->formatFood($food, $locale), $this->foodRepository->getAll());
    }

    public function getFoodById(int $id, string $locale = 'zh'): array|null
    {
        $food = $this->foodRepository->getById($id);

        return $food === null ? null : $this->formatFood($food, $locale);
    }

    public function getFoodsByCategory(string $categorySlug, string $locale = 'zh'): array
    {
        return array_map(
            fn (array $food): array => $this->formatFood($food, $locale),
            $this->foodRepository->getByCategory($categorySlug)
        );
    }

    public function searchFoods(string $query, ?string $categorySlug = null, string $locale = 'zh'): array
    {
        return array_map(
            fn (array $food): array => $this->formatFood($food, $locale),
            $this->foodRepository->search($query, $categorySlug)
        );
    }

    public function getCategories(string $locale = 'zh'): array
    {
        $resolvedLocale = $this->resolveLocale($locale);

        return array_map(function (array $category) use ($resolvedLocale): array {
            return [
                'id' => (int) ($category['id'] ?? 0),
                'slug' => (string) ($category['slug'] ?? ''),
                'name' => (string) ($category['name_' . $resolvedLocale] ?? $category['name_zh'] ?? ''),
                'icon' => (string) ($category['icon'] ?? ''),
            ];
        }, $this->foodRepository->getCategories());
    }

    public function formatFood(array $food, string $locale): array
    {
        $resolvedLocale = $this->resolveLocale($locale);

        return [
            'id' => (int) ($food['food_id'] ?? $food['id'] ?? 0),
            'name' => $this->resolveLocalizedValue($food, 'name', $resolvedLocale),
            'description' => $this->resolveLocalizedValue($food, 'description', $resolvedLocale),
            'image_url' => (string) ($food['image_url'] ?? ''),
            'latitude' => isset($food['latitude']) ? (float) $food['latitude'] : null,
            'longitude' => isset($food['longitude']) ? (float) $food['longitude'] : null,
            'area' => $this->resolveLocalizedValue($food, 'area', $resolvedLocale),
            'price_range' => (string) ($food['price_range'] ?? ''),
            'rating' => isset($food['rating']) ? (float) $food['rating'] : 0.0,
            'category_name' => $this->resolveLocalizedValue($food, 'category_name', $resolvedLocale),
            'category_slug' => (string) ($food['category_slug'] ?? ''),
            'category_icon' => (string) ($food['category_icon'] ?? ''),
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
