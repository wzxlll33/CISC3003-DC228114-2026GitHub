<?php

namespace App\Repositories;

use App\Core\Database;

class ReviewRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function getByRestaurant(int $restaurantId, int $limit = 20): array
    {
        $safeLimit = max(1, $limit);
        $statement = $this->db->pdo()->prepare(
            'SELECT
                reviews.*,
                users.username,
                users.avatar_url,
                foods.name_en AS food_name_en,
                foods.name_zh AS food_name_zh,
                foods.name_pt AS food_name_pt
             FROM reviews
             INNER JOIN users ON users.id = reviews.user_id
             LEFT JOIN foods ON foods.id = reviews.food_id
             WHERE reviews.restaurant_id = :restaurant_id
             ORDER BY reviews.created_at DESC, reviews.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':restaurant_id', $restaurantId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $safeLimit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getByUser(int $userId, int $limit = 20): array
    {
        $safeLimit = max(1, $limit);
        $statement = $this->db->pdo()->prepare(
            'SELECT
                reviews.*,
                users.username,
                users.avatar_url,
                restaurants.name_en AS restaurant_name_en,
                restaurants.name_zh AS restaurant_name_zh,
                restaurants.name_pt AS restaurant_name_pt,
                foods.name_en AS food_name_en,
                foods.name_zh AS food_name_zh,
                foods.name_pt AS food_name_pt
             FROM reviews
             INNER JOIN users ON users.id = reviews.user_id
             INNER JOIN restaurants ON restaurants.id = reviews.restaurant_id
             LEFT JOIN foods ON foods.id = reviews.food_id
             WHERE reviews.user_id = :user_id
             ORDER BY reviews.created_at DESC, reviews.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $safeLimit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(int $userId, int $restaurantId, int|null $foodId, int $rating, string $comment): array|null
    {
        $reviewId = $this->db->insert('reviews', [
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'food_id' => $foodId,
            'rating' => $rating,
            'comment' => trim($comment),
        ]);

        (new RestaurantRepository($this->db))->updateRating($restaurantId);

        return $this->findById((int) $reviewId);
    }

    public function update(int $reviewId, int $userId, int $rating, string $comment): array|null
    {
        $review = $this->findById($reviewId);

        if ($review === null || (int) ($review['user_id'] ?? 0) !== $userId) {
            return null;
        }

        $this->db->query(
            'UPDATE reviews
             SET rating = :rating, comment = :comment, updated_at = CURRENT_TIMESTAMP
             WHERE id = :review_id AND user_id = :user_id',
            [
                ':rating' => $rating,
                ':comment' => trim($comment),
                ':review_id' => $reviewId,
                ':user_id' => $userId,
            ]
        );

        (new RestaurantRepository($this->db))->updateRating((int) $review['restaurant_id']);

        return $this->findById($reviewId);
    }

    public function delete(int $reviewId, int $userId): bool
    {
        $review = $this->findById($reviewId);

        if ($review === null || (int) ($review['user_id'] ?? 0) !== $userId) {
            return false;
        }

        $deleted = $this->db->delete('reviews', 'id = :id AND user_id = :user_id', [
            ':id' => $reviewId,
            ':user_id' => $userId,
        ]);

        if ($deleted > 0) {
            (new RestaurantRepository($this->db))->updateRating((int) $review['restaurant_id']);
            return true;
        }

        return false;
    }

    public function getStats(int $restaurantId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT rating, COUNT(*) AS aggregate
             FROM reviews
             WHERE restaurant_id = :restaurant_id
             GROUP BY rating
             ORDER BY rating DESC',
            [':restaurant_id' => $restaurantId]
        );

        $distribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        foreach ($rows as $row) {
            $distribution[(int) $row['rating']] = (int) ($row['aggregate'] ?? 0);
        }

        return $distribution;
    }

    public function hasUserReviewed(int $userId, int $restaurantId): bool
    {
        $result = $this->db->fetch(
            'SELECT 1 FROM reviews WHERE user_id = :user_id AND restaurant_id = :restaurant_id LIMIT 1',
            [
                ':user_id' => $userId,
                ':restaurant_id' => $restaurantId,
            ]
        );

        return $result !== false;
    }

    public function findById(int $reviewId): array|null
    {
        $review = $this->db->fetch(
            'SELECT
                reviews.*,
                users.username,
                users.avatar_url,
                restaurants.name_en AS restaurant_name_en,
                restaurants.name_zh AS restaurant_name_zh,
                restaurants.name_pt AS restaurant_name_pt,
                foods.name_en AS food_name_en,
                foods.name_zh AS food_name_zh,
                foods.name_pt AS food_name_pt
             FROM reviews
             INNER JOIN users ON users.id = reviews.user_id
             INNER JOIN restaurants ON restaurants.id = reviews.restaurant_id
             LEFT JOIN foods ON foods.id = reviews.food_id
             WHERE reviews.id = :review_id
             LIMIT 1',
            [':review_id' => $reviewId]
        );

        return $review === false ? null : $review;
    }
}
