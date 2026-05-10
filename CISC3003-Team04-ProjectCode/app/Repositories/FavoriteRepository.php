<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class FavoriteRepository
{
    private PDO $pdo;

    public function __construct(private readonly Database $db)
    {
        $this->pdo = $db->pdo();
    }

    public function toggle(int $userId, int $foodId): array
    {
        if ($this->isFavorited($userId, $foodId)) {
            $statement = $this->pdo->prepare('DELETE FROM favorites WHERE user_id = :user_id AND food_id = :food_id');
            $statement->execute([
                'user_id' => $userId,
                'food_id' => $foodId,
            ]);

            return ['action' => 'removed'];
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO favorites (user_id, food_id, created_at) VALUES (:user_id, :food_id, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'user_id' => $userId,
            'food_id' => $foodId,
        ]);

        return ['action' => 'added'];
    }

    public function add(int $userId, int $foodId): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT OR IGNORE INTO favorites (user_id, food_id, created_at) VALUES (:user_id, :food_id, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'user_id' => $userId,
            'food_id' => $foodId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function isFavorited(int $userId, int $foodId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM favorites WHERE user_id = :user_id AND food_id = :food_id LIMIT 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'food_id' => $foodId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function getUserFavorites(int $userId, int $limit = 50): array
    {
        $safeLimit = max(1, $limit);
        $statement = $this->pdo->prepare(
            'SELECT
                favorites.id AS favorite_id,
                favorites.created_at AS favorited_at,
                foods.*,
                categories.slug AS category_slug,
                categories.icon AS category_icon,
                categories.name_en AS category_name_en,
                categories.name_zh AS category_name_zh,
                categories.name_pt AS category_name_pt
             FROM favorites
             INNER JOIN foods ON foods.id = favorites.food_id
             INNER JOIN categories ON categories.id = foods.category_id
             WHERE favorites.user_id = :user_id
             ORDER BY favorites.created_at DESC, favorites.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $safeLimit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getFavoriteIds(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT food_id FROM favorites WHERE user_id = :user_id ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map(static fn (mixed $value): int => (int) $value, $statement->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function count(int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }
}
