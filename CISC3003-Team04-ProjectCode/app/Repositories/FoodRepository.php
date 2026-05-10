<?php

namespace App\Repositories;

use App\Core\Database;

class FoodRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function getAll(): array
    {
        return $this->db->fetchAll($this->baseFoodSelect() . ' ORDER BY foods.rating DESC, foods.id ASC');
    }

    public function getById(int $id): array|null
    {
        $food = $this->db->fetch(
            $this->baseFoodSelect() . ' WHERE foods.id = :id ORDER BY foods.rating DESC, foods.id ASC',
            [':id' => $id]
        );

        return $food === false ? null : $food;
    }

    public function getByRestaurant(int $restaurantId): array
    {
        return $this->db->fetchAll(
            $this->baseFoodSelect('food_restaurant.is_signature AS is_signature') . '
             INNER JOIN food_restaurant ON food_restaurant.food_id = foods.id
             WHERE food_restaurant.restaurant_id = :restaurant_id
             ORDER BY food_restaurant.is_signature DESC, foods.rating DESC, foods.id ASC',
            [':restaurant_id' => $restaurantId]
        );
    }

    public function getForRestaurant(int $foodId, int $restaurantId): array|null
    {
        $food = $this->db->fetch(
            $this->baseFoodSelect('food_restaurant.is_signature AS is_signature') . '
             INNER JOIN food_restaurant ON food_restaurant.food_id = foods.id
             WHERE foods.id = :food_id AND food_restaurant.restaurant_id = :restaurant_id
             LIMIT 1',
            [
                ':food_id' => $foodId,
                ':restaurant_id' => $restaurantId,
            ]
        );

        return $food === false ? null : $food;
    }

    public function getByCategory(string $categorySlug): array
    {
        return $this->db->fetchAll(
            $this->baseFoodSelect() . ' WHERE categories.slug = :category_slug ORDER BY foods.rating DESC, foods.id ASC',
            [':category_slug' => $categorySlug]
        );
    }

    public function search(string $query, ?string $categorySlug = null): array
    {
        $params = [
            ':search' => '%' . trim($query) . '%',
        ];

        $sql = $this->baseFoodSelect() . ' WHERE (
                foods.name_en LIKE :search
                OR foods.name_zh LIKE :search
                OR foods.name_pt LIKE :search
                OR foods.description_en LIKE :search
                OR foods.description_zh LIKE :search
                OR foods.description_pt LIKE :search
                OR foods.area_en LIKE :search
                OR foods.area_zh LIKE :search
                OR foods.area_pt LIKE :search
            )';

        if ($categorySlug !== null && $categorySlug !== '') {
            $sql .= ' AND categories.slug = :category_slug';
            $params[':category_slug'] = $categorySlug;
        }

        $sql .= ' ORDER BY foods.rating DESC, foods.id ASC';

        return $this->db->fetchAll($sql, $params);
    }

    public function getCategories(): array
    {
        return $this->db->fetchAll('SELECT * FROM categories ORDER BY id ASC');
    }

    public function create(array $data): int
    {
        return (int) $this->db->insert('foods', $this->foodPayload($data));
    }

    public function update(int $foodId, array $data): bool
    {
        $payload = $this->foodPayload($data);

        if ($payload === []) {
            return false;
        }

        return $this->db->update('foods', $payload, 'id = :id', [':id' => $foodId]) > 0;
    }

    public function delete(int $foodId): bool
    {
        return $this->db->delete('foods', 'id = :id', [':id' => $foodId]) > 0;
    }

    public function getRestaurantLinks(int $foodId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT restaurant_id, is_signature
             FROM food_restaurant
             WHERE food_id = :food_id
             ORDER BY restaurant_id ASC',
            [':food_id' => $foodId]
        );
        $links = [];

        foreach ($rows as $row) {
            $links[(int) ($row['restaurant_id'] ?? 0)] = [
                'restaurant_id' => (int) ($row['restaurant_id'] ?? 0),
                'is_signature' => !empty($row['is_signature']),
            ];
        }

        return $links;
    }

    public function syncRestaurantLinks(int $foodId, array $links): void
    {
        $this->db->delete('food_restaurant', 'food_id = :food_id', [':food_id' => $foodId]);

        foreach ($links as $link) {
            $restaurantId = (int) ($link['restaurant_id'] ?? 0);

            if ($restaurantId <= 0) {
                continue;
            }

            $this->db->insert('food_restaurant', [
                'food_id' => $foodId,
                'restaurant_id' => $restaurantId,
                'is_signature' => !empty($link['is_signature']) ? 1 : 0,
            ]);
        }
    }

    public function upsertRestaurantLink(int $foodId, int $restaurantId, bool $isSignature): void
    {
        $this->db->query(
            'INSERT INTO food_restaurant (food_id, restaurant_id, is_signature)
             VALUES (:food_id, :restaurant_id, :is_signature)
             ON CONFLICT(food_id, restaurant_id) DO UPDATE SET is_signature = excluded.is_signature',
            [
                ':food_id' => $foodId,
                ':restaurant_id' => $restaurantId,
                ':is_signature' => $isSignature ? 1 : 0,
            ]
        );
    }

    public function removeRestaurantLink(int $foodId, int $restaurantId): bool
    {
        return $this->db->delete(
            'food_restaurant',
            'food_id = :food_id AND restaurant_id = :restaurant_id',
            [
                ':food_id' => $foodId,
                ':restaurant_id' => $restaurantId,
            ]
        ) > 0;
    }

    public function getCategoryBySlug(string $slug): array|null
    {
        $category = $this->db->fetch('SELECT * FROM categories WHERE slug = :slug LIMIT 1', [':slug' => $slug]);

        return $category === false ? null : $category;
    }

    private function baseFoodSelect(?string $extraSelect = null): string
    {
        $extra = $extraSelect !== null && trim($extraSelect) !== '' ? ",\n                " . $extraSelect : '';

        return 'SELECT
                foods.*,
                categories.slug AS category_slug,
                categories.icon AS category_icon,
                categories.name_en AS category_name_en,
                categories.name_zh AS category_name_zh,
                categories.name_pt AS category_name_pt' . $extra . '
            FROM foods
            INNER JOIN categories ON categories.id = foods.category_id';
    }

    private function foodPayload(array $data): array
    {
        $columns = [
            'category_id',
            'name_en',
            'name_zh',
            'name_pt',
            'description_en',
            'description_zh',
            'description_pt',
            'image_url',
            'latitude',
            'longitude',
            'area_en',
            'area_zh',
            'area_pt',
            'price_range',
            'rating',
        ];
        $payload = [];

        foreach ($columns as $column) {
            if (array_key_exists($column, $data)) {
                $payload[$column] = $data[$column];
            }
        }

        return $payload;
    }
}
