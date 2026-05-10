<?php

namespace App\Services;

use App\Repositories\HistoryRepository;

class SearchService
{
    public function __construct(
        private readonly FoodService $foodService,
        private readonly HistoryRepository $historyRepository
    ) {
    }

    public function search(string $query, ?string $categorySlug, int|null $userId, string $locale): array
    {
        $normalizedQuery = trim($query);
        $normalizedCategory = $categorySlug !== null && $categorySlug !== '' ? $categorySlug : null;
        $results = $normalizedQuery === ''
            ? ($normalizedCategory === null
                ? $this->foodService->getAllFoods($locale)
                : $this->foodService->getFoodsByCategory($normalizedCategory, $locale))
            : $this->foodService->searchFoods($normalizedQuery, $normalizedCategory, $locale);

        if ($userId !== null && $userId > 0 && $normalizedQuery !== '') {
            $filtersJson = json_encode(['category' => $normalizedCategory], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (!$this->historyRepository->hasRecentSearch($userId, $normalizedQuery, $filtersJson)) {
                $this->historyRepository->logSearch($userId, $normalizedQuery, $filtersJson, count($results));
            }
        }

        return $results;
    }
}
