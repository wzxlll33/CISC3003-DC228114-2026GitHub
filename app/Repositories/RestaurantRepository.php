<?php

namespace App\Repositories;

use App\Core\Database;

class RestaurantRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function getAll(): array
    {
        return $this->attachTags(
            $this->db->fetchAll(
                'SELECT * FROM restaurants ORDER BY avg_rating DESC, review_count DESC, id ASC'
            )
        );
    }

    public function getById(int $id): array|null
    {
        $restaurant = $this->db->fetch('SELECT * FROM restaurants WHERE id = :id LIMIT 1', [':id' => $id]);

        if ($restaurant === false) {
            return null;
        }

        $restaurants = $this->attachTags([$restaurant]);
        $restaurant = $restaurants[0] ?? $restaurant;
        $restaurant['foods'] = $this->getFoodsForRestaurant($id);

        return $restaurant;
    }

    public function getByArea(string $area): array
    {
        $normalizedArea = trim($area);

        return $this->attachTags(
            $this->db->fetchAll(
                'SELECT *
                 FROM restaurants
                 WHERE area_en = :area OR area_zh = :area OR area_pt = :area
                 ORDER BY avg_rating DESC, review_count DESC, id ASC',
                [':area' => $normalizedArea]
            )
        );
    }

    public function search(string $query): array
    {
        $term = '%' . trim($query) . '%';

        return $this->attachTags(
            $this->db->fetchAll(
                'SELECT *
                 FROM restaurants
                 WHERE name_en LIKE :search OR name_zh LIKE :search OR name_pt LIKE :search
                 ORDER BY avg_rating DESC, review_count DESC, id ASC',
                [':search' => $term]
            )
        );
    }

    public function getNearby(float $lat, float $lng, float $radiusKm = 2): array
    {
        $restaurants = $this->attachTags($this->db->fetchAll('SELECT * FROM restaurants ORDER BY id ASC'));
        $nearby = [];

        foreach ($restaurants as $restaurant) {
            $distanceKm = $this->haversineDistance(
                $lat,
                $lng,
                (float) ($restaurant['latitude'] ?? 0),
                (float) ($restaurant['longitude'] ?? 0)
            );

            if ($distanceKm > $radiusKm) {
                continue;
            }

            $restaurant['distance_km'] = round($distanceKm, 2);
            $nearby[] = $restaurant;
        }

        usort($nearby, static function (array $left, array $right): int {
            return ($left['distance_km'] <=> $right['distance_km'])
                ?: (($right['avg_rating'] ?? 0) <=> ($left['avg_rating'] ?? 0))
                ?: (($right['review_count'] ?? 0) <=> ($left['review_count'] ?? 0));
        });

        return $nearby;
    }

    public function getTopRated(int $limit = 10): array
    {
        $safeLimit = max(1, $limit);
        $statement = $this->db->pdo()->prepare(
            'SELECT *
             FROM restaurants
             ORDER BY avg_rating DESC, review_count DESC, id ASC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $safeLimit, \PDO::PARAM_INT);
        $statement->execute();

        return $this->attachTags($statement->fetchAll());
    }

    public function getByFood(int $foodId): array
    {
        return $this->attachTags(
            $this->db->fetchAll(
                'SELECT restaurants.*
                 FROM food_restaurant
                 INNER JOIN restaurants ON restaurants.id = food_restaurant.restaurant_id
                 WHERE food_restaurant.food_id = :food_id
                 ORDER BY food_restaurant.is_signature DESC, restaurants.avg_rating DESC, restaurants.id ASC',
                [':food_id' => $foodId]
            )
        );
    }

    public function create(array $data): int
    {
        return (int) $this->db->insert('restaurants', $this->restaurantPayload($data));
    }

    public function update(int $restaurantId, array $data): bool
    {
        $payload = $this->restaurantPayload($data);

        if ($payload === []) {
            return false;
        }

        return $this->db->update('restaurants', $payload, 'id = :id', [':id' => $restaurantId]) > 0;
    }

    public function delete(int $restaurantId): bool
    {
        return $this->db->delete('restaurants', 'id = :id', [':id' => $restaurantId]) > 0;
    }

    public function updateRating(int $restaurantId): void
    {
        $aggregate = $this->db->fetch(
            'SELECT ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS review_count
             FROM reviews
             WHERE restaurant_id = :restaurant_id',
            [':restaurant_id' => $restaurantId]
        );

        $this->db->query(
            'UPDATE restaurants
             SET avg_rating = :avg_rating, review_count = :review_count
             WHERE id = :restaurant_id',
            [
                ':avg_rating' => isset($aggregate['avg_rating']) && $aggregate['avg_rating'] !== null
                    ? (float) $aggregate['avg_rating']
                    : 0.0,
                ':review_count' => (int) ($aggregate['review_count'] ?? 0),
                ':restaurant_id' => $restaurantId,
            ]
        );
    }

    private function getFoodsForRestaurant(int $restaurantId): array
    {
        return $this->db->fetchAll(
            'SELECT
                foods.*,
                food_restaurant.is_signature,
                categories.slug AS category_slug,
                categories.icon AS category_icon,
                categories.name_en AS category_name_en,
                categories.name_zh AS category_name_zh,
                categories.name_pt AS category_name_pt
             FROM food_restaurant
             INNER JOIN foods ON foods.id = food_restaurant.food_id
             INNER JOIN categories ON categories.id = foods.category_id
             WHERE food_restaurant.restaurant_id = :restaurant_id
             ORDER BY food_restaurant.is_signature DESC, foods.rating DESC, foods.id ASC',
            [':restaurant_id' => $restaurantId]
        );
    }

    private function restaurantPayload(array $data): array
    {
        $columns = [
            'name_en',
            'name_zh',
            'name_pt',
            'description_en',
            'description_zh',
            'description_pt',
            'address_en',
            'address_zh',
            'address_pt',
            'phone',
            'opening_hours',
            'price_range',
            'google_rating',
            'amap_rating',
            'avg_rating',
            'latitude',
            'longitude',
            'area_en',
            'area_zh',
            'area_pt',
            'image_url',
        ];
        $payload = [];

        foreach ($columns as $column) {
            if (array_key_exists($column, $data)) {
                $payload[$column] = $data[$column];
            }
        }

        return $payload;
    }

    private function attachTags(array $restaurants): array
    {
        if ([] === $restaurants) {
            return [];
        }

        $restaurantIds = array_values(array_unique(array_map(
            static fn (array $restaurant): int => (int) ($restaurant['id'] ?? 0),
            $restaurants
        )));
        $tagRows = $this->getTagsForRestaurantIds($restaurantIds);
        $tagLookup = [];

        foreach ($tagRows as $tagRow) {
            $tagLookup[(int) $tagRow['restaurant_id']][] = [
                'id' => (int) ($tagRow['id'] ?? 0),
                'slug' => (string) ($tagRow['slug'] ?? ''),
                'name_en' => (string) ($tagRow['name_en'] ?? ''),
                'name_zh' => (string) ($tagRow['name_zh'] ?? ''),
                'name_pt' => (string) ($tagRow['name_pt'] ?? ''),
            ];
        }

        return array_map(static function (array $restaurant) use ($tagLookup): array {
            $restaurant['tags'] = $tagLookup[(int) ($restaurant['id'] ?? 0)] ?? [];

            return $restaurant;
        }, $restaurants);
    }

    private function getTagsForRestaurantIds(array $restaurantIds): array
    {
        if ([] === $restaurantIds) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($restaurantIds as $index => $restaurantId) {
            $placeholder = ':restaurant_id_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $restaurantId;
        }

        return $this->db->fetchAll(
            'SELECT
                restaurant_tags.restaurant_id,
                tags.id,
                tags.slug,
                tags.name_en,
                tags.name_zh,
                tags.name_pt
             FROM restaurant_tags
             INNER JOIN tags ON tags.id = restaurant_tags.tag_id
             WHERE restaurant_tags.restaurant_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY tags.id ASC',
            $params
        );
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
