<?php

namespace App\Repositories;

use App\Core\Database;

class HistoryRepository
{
    public function __construct(private readonly Database $db)
    {
        $this->ensureRestaurantBrowseSchema();
    }

    public function logSearch(int $userId, string $query, ?string $filtersJson, int $resultsCount): string
    {
        return $this->db->insert('search_history', [
            'user_id' => $userId,
            'query' => trim($query),
            'filters_json' => $filtersJson,
            'results_count' => $resultsCount,
        ]);
    }

    public function logBrowse(int $userId, int $foodId): bool
    {
        $existing = $this->db->fetch(
            'SELECT id
             FROM browse_history
             WHERE user_id = :user_id
               AND food_id = :food_id
               AND created_at >= DATETIME("now", "-5 minutes")
             ORDER BY created_at DESC
             LIMIT 1',
            [
                ':user_id' => $userId,
                ':food_id' => $foodId,
            ]
        );

        if ($existing !== false) {
            return false;
        }

        $this->db->insert('browse_history', [
            'user_id' => $userId,
            'food_id' => $foodId,
        ]);

        return true;
    }

    public function logRestaurantBrowse(int $userId, int $restaurantId): bool
    {
        $existing = $this->db->fetch(
            'SELECT id
             FROM restaurant_browse_history
             WHERE user_id = :user_id
               AND restaurant_id = :restaurant_id
               AND created_at >= DATETIME("now", "-5 minutes")
             ORDER BY created_at DESC
             LIMIT 1',
            [
                ':user_id' => $userId,
                ':restaurant_id' => $restaurantId,
            ]
        );

        if ($existing !== false) {
            return false;
        }

        $this->db->insert('restaurant_browse_history', [
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
        ]);

        return true;
    }

    public function getSearchHistory(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT id, query, filters_json, results_count, created_at
             FROM search_history
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit',
            [
                ':user_id' => $userId,
                ':limit' => max(1, $limit),
            ]
        );
    }

    public function getBrowseHistory(int $userId, int $limit = 20): array
    {
        $safeLimit = max(1, $limit);

        return $this->db->fetchAll(
            'SELECT *
             FROM (
                SELECT
                    "food" AS item_type,
                    browse_history.id,
                    browse_history.created_at,
                    foods.id AS food_id,
                    NULL AS restaurant_id,
                    foods.image_url,
                    foods.latitude,
                    foods.longitude,
                    foods.price_range,
                    foods.rating,
                    foods.name_en,
                    foods.name_zh,
                    foods.name_pt,
                    foods.description_en,
                    foods.description_zh,
                    foods.description_pt,
                    foods.area_en,
                    foods.area_zh,
                    foods.area_pt,
                    categories.slug AS category_slug,
                    categories.icon AS category_icon,
                    categories.name_en AS category_name_en,
                    categories.name_zh AS category_name_zh,
                    categories.name_pt AS category_name_pt,
                    NULL AS address_en,
                    NULL AS address_zh,
                    NULL AS address_pt,
                    NULL AS phone,
                    NULL AS opening_hours,
                    NULL AS avg_rating,
                    NULL AS review_count
                FROM browse_history
                INNER JOIN foods ON foods.id = browse_history.food_id
                INNER JOIN categories ON categories.id = foods.category_id
                WHERE browse_history.user_id = :food_user_id

                UNION ALL

                SELECT
                    "restaurant" AS item_type,
                    restaurant_browse_history.id,
                    restaurant_browse_history.created_at,
                    NULL AS food_id,
                    restaurants.id AS restaurant_id,
                    restaurants.image_url,
                    restaurants.latitude,
                    restaurants.longitude,
                    restaurants.price_range,
                    restaurants.avg_rating AS rating,
                    restaurants.name_en,
                    restaurants.name_zh,
                    restaurants.name_pt,
                    restaurants.description_en,
                    restaurants.description_zh,
                    restaurants.description_pt,
                    restaurants.area_en,
                    restaurants.area_zh,
                    restaurants.area_pt,
                    NULL AS category_slug,
                    NULL AS category_icon,
                    NULL AS category_name_en,
                    NULL AS category_name_zh,
                    NULL AS category_name_pt,
                    restaurants.address_en,
                    restaurants.address_zh,
                    restaurants.address_pt,
                    restaurants.phone,
                    restaurants.opening_hours,
                    restaurants.avg_rating,
                    restaurants.review_count
                FROM restaurant_browse_history
                INNER JOIN restaurants ON restaurants.id = restaurant_browse_history.restaurant_id
                WHERE restaurant_browse_history.user_id = :restaurant_user_id
             )
             ORDER BY created_at DESC, id DESC
             LIMIT :limit',
            [
                ':food_user_id' => $userId,
                ':restaurant_user_id' => $userId,
                ':limit' => $safeLimit,
            ]
        );
    }

    public function countBrowseHistory(int $userId): int
    {
        $row = $this->db->fetch(
            'SELECT
                (SELECT COUNT(*) FROM browse_history WHERE user_id = :food_user_id)
                + (SELECT COUNT(*) FROM restaurant_browse_history WHERE user_id = :restaurant_user_id) AS aggregate',
            [
                ':food_user_id' => $userId,
                ':restaurant_user_id' => $userId,
            ]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function getPaginatedBrowseHistory(int $userId, int $limit, int $offset): array
    {
        return $this->db->fetchAll(
            'SELECT *
             FROM (
                SELECT
                    "food" AS item_type,
                    browse_history.id,
                    browse_history.created_at,
                    foods.id AS food_id,
                    NULL AS restaurant_id,
                    foods.image_url,
                    foods.latitude,
                    foods.longitude,
                    foods.price_range,
                    foods.rating,
                    foods.name_en,
                    foods.name_zh,
                    foods.name_pt,
                    foods.description_en,
                    foods.description_zh,
                    foods.description_pt,
                    foods.area_en,
                    foods.area_zh,
                    foods.area_pt,
                    categories.slug AS category_slug,
                    categories.icon AS category_icon,
                    categories.name_en AS category_name_en,
                    categories.name_zh AS category_name_zh,
                    categories.name_pt AS category_name_pt,
                    NULL AS address_en,
                    NULL AS address_zh,
                    NULL AS address_pt,
                    NULL AS phone,
                    NULL AS opening_hours,
                    NULL AS avg_rating,
                    NULL AS review_count
                FROM browse_history
                INNER JOIN foods ON foods.id = browse_history.food_id
                INNER JOIN categories ON categories.id = foods.category_id
                WHERE browse_history.user_id = :food_user_id

                UNION ALL

                SELECT
                    "restaurant" AS item_type,
                    restaurant_browse_history.id,
                    restaurant_browse_history.created_at,
                    NULL AS food_id,
                    restaurants.id AS restaurant_id,
                    restaurants.image_url,
                    restaurants.latitude,
                    restaurants.longitude,
                    restaurants.price_range,
                    restaurants.avg_rating AS rating,
                    restaurants.name_en,
                    restaurants.name_zh,
                    restaurants.name_pt,
                    restaurants.description_en,
                    restaurants.description_zh,
                    restaurants.description_pt,
                    restaurants.area_en,
                    restaurants.area_zh,
                    restaurants.area_pt,
                    NULL AS category_slug,
                    NULL AS category_icon,
                    NULL AS category_name_en,
                    NULL AS category_name_zh,
                    NULL AS category_name_pt,
                    restaurants.address_en,
                    restaurants.address_zh,
                    restaurants.address_pt,
                    restaurants.phone,
                    restaurants.opening_hours,
                    restaurants.avg_rating,
                    restaurants.review_count
                FROM restaurant_browse_history
                INNER JOIN restaurants ON restaurants.id = restaurant_browse_history.restaurant_id
                WHERE restaurant_browse_history.user_id = :restaurant_user_id
             )
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset',
            [
                ':food_user_id' => $userId,
                ':restaurant_user_id' => $userId,
                ':limit' => max(1, $limit),
                ':offset' => max(0, $offset),
            ]
        );
    }

    public function getFoodBrowseHistory(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT
                browse_history.id,
                browse_history.created_at,
                foods.id AS food_id,
                foods.image_url,
                foods.latitude,
                foods.longitude,
                foods.price_range,
                foods.rating,
                foods.name_en,
                foods.name_zh,
                foods.name_pt,
                foods.description_en,
                foods.description_zh,
                foods.description_pt,
                foods.area_en,
                foods.area_zh,
                foods.area_pt,
                categories.slug AS category_slug,
                categories.icon AS category_icon,
                categories.name_en AS category_name_en,
                categories.name_zh AS category_name_zh,
                categories.name_pt AS category_name_pt
             FROM browse_history
             INNER JOIN foods ON foods.id = browse_history.food_id
             INNER JOIN categories ON categories.id = foods.category_id
             WHERE browse_history.user_id = :user_id
             ORDER BY browse_history.created_at DESC, browse_history.id DESC
             LIMIT :limit',
            [
                ':user_id' => $userId,
                ':limit' => max(1, $limit),
            ]
        );
    }

    public function clearSearchHistory(int $userId): int
    {
        return $this->db->delete('search_history', 'user_id = :user_id', [':user_id' => $userId]);
    }

    public function clearBrowseHistory(int $userId): int
    {
        $deletedFoods = $this->db->delete('browse_history', 'user_id = :user_id', [':user_id' => $userId]);
        $deletedRestaurants = $this->db->delete('restaurant_browse_history', 'user_id = :user_id', [':user_id' => $userId]);

        return $deletedFoods + $deletedRestaurants;
    }

    public function hasRecentSearch(int $userId, string $query, ?string $filtersJson, int $seconds = 30): bool
    {
        $existing = $this->db->fetch(
            'SELECT id
             FROM search_history
             WHERE user_id = :user_id
               AND query = :query
               AND ((filters_json IS NULL AND :filters_json IS NULL) OR filters_json = :filters_json)
               AND created_at >= DATETIME("now", :window)
             ORDER BY created_at DESC
             LIMIT 1',
            [
                ':user_id' => $userId,
                ':query' => trim($query),
                ':filters_json' => $filtersJson,
                ':window' => '-' . max(1, $seconds) . ' seconds',
            ]
        );

        return $existing !== false;
    }

    private function ensureRestaurantBrowseSchema(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS restaurant_browse_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                restaurant_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            )'
        );
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_restaurant_browse_history_user ON restaurant_browse_history(user_id)');
    }
}
