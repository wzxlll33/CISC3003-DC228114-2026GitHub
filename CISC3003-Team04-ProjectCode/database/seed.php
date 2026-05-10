<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$configPath = $basePath . '/config/database.php';
$schemaPath = __DIR__ . '/schema.sql';
$seedPath = __DIR__ . '/seeds/foods.json';

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function randomTimestamp(int $maxDaysAgo = 90): string
{
    $daysAgo = mt_rand(0, $maxDaysAgo);
    $hoursAgo = mt_rand(0, 23);
    $minutesAgo = mt_rand(0, 59);

    return date('Y-m-d H:i:s', strtotime(sprintf('-%d days -%d hours -%d minutes', $daysAgo, $hoursAgo, $minutesAgo)));
}

if (!file_exists($configPath)) {
    throw new RuntimeException('Missing database config: ' . $configPath);
}

$config = require $configPath;

if (!is_array($config)) {
    throw new RuntimeException('Database config must return an array.');
}

$databasePath = $config['database'] ?? null;

if (!is_string($databasePath) || $databasePath === '') {
    throw new RuntimeException('Database config must define a non-empty "database" path.');
}

if (($config['driver'] ?? 'sqlite') !== 'sqlite') {
    throw new RuntimeException('This seed script only supports the sqlite driver.');
}

$databaseDir = dirname($databasePath);

if (!is_dir($databaseDir) && !mkdir($databaseDir, 0777, true) && !is_dir($databaseDir)) {
    throw new RuntimeException('Unable to create database directory: ' . $databaseDir);
}

out('Preparing SQLite database...');

if (file_exists($databasePath) && !unlink($databasePath)) {
    throw new RuntimeException('Unable to reset database file: ' . $databasePath);
}

$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON;');

out('Applying schema...');

$schemaSql = file_get_contents($schemaPath);

if ($schemaSql === false) {
    throw new RuntimeException('Unable to read schema file: ' . $schemaPath);
}

$pdo->exec($schemaSql);

out('Loading seed data...');

$seedJson = file_get_contents($seedPath);

if ($seedJson === false) {
    throw new RuntimeException('Unable to read seed file: ' . $seedPath);
}

$payload = json_decode($seedJson, true, 512, JSON_THROW_ON_ERROR);
$categories = $payload['categories'] ?? [];
$foods = $payload['foods'] ?? [];
$restaurants = $payload['restaurants'] ?? [];
$tags = $payload['tags'] ?? [];
$foodTags = $payload['food_tags'] ?? [];
$restaurantTags = $payload['restaurant_tags'] ?? [];
$reviewUsers = $payload['review_users'] ?? [];
$seededReviews = $payload['reviews'] ?? [];
$seedSampleReviews = (bool) ($payload['sample_reviews'] ?? true);

foreach ([
    'categories' => $categories,
    'foods' => $foods,
    'restaurants' => $restaurants,
    'tags' => $tags,
    'food_tags' => $foodTags,
    'restaurant_tags' => $restaurantTags,
    'review_users' => $reviewUsers,
    'reviews' => $seededReviews,
] as $key => $value) {
    if (!is_array($value)) {
        throw new RuntimeException('Seed file must contain a valid ' . $key . ' array.');
    }
}

mt_srand(3003);

$pdo->beginTransaction();

try {
    $insertCategory = $pdo->prepare(
        'INSERT INTO categories (slug, name_en, name_zh, name_pt, icon) VALUES (:slug, :name_en, :name_zh, :name_pt, :icon)'
    );

    $categoryMap = [];

    foreach ($categories as $category) {
        $insertCategory->execute([
            ':slug' => $category['slug'],
            ':name_en' => $category['name_en'],
            ':name_zh' => $category['name_zh'],
            ':name_pt' => $category['name_pt'],
            ':icon' => $category['icon'] ?? null,
        ]);

        $categoryMap[$category['slug']] = (int) $pdo->lastInsertId();
    }

    out('Inserted ' . count($categoryMap) . ' categories.');

    $insertFood = $pdo->prepare(
        'INSERT INTO foods (
            category_id,
            name_en,
            name_zh,
            name_pt,
            description_en,
            description_zh,
            description_pt,
            image_url,
            latitude,
            longitude,
            area_en,
            area_zh,
            area_pt,
            price_range,
            rating
        ) VALUES (
            :category_id,
            :name_en,
            :name_zh,
            :name_pt,
            :description_en,
            :description_zh,
            :description_pt,
            :image_url,
            :latitude,
            :longitude,
            :area_en,
            :area_zh,
            :area_pt,
            :price_range,
            :rating
        )'
    );

    $foodIdMap = [];

    foreach ($foods as $index => $food) {
        $categorySlug = $food['category_slug'] ?? '';

        if (!isset($categoryMap[$categorySlug])) {
            throw new RuntimeException('Unknown category slug in food seed: ' . $categorySlug);
        }

        $insertFood->execute([
            ':category_id' => $categoryMap[$categorySlug],
            ':name_en' => $food['name_en'],
            ':name_zh' => $food['name_zh'],
            ':name_pt' => $food['name_pt'],
            ':description_en' => $food['description_en'],
            ':description_zh' => $food['description_zh'],
            ':description_pt' => $food['description_pt'],
            ':image_url' => $food['image_url'],
            ':latitude' => $food['latitude'],
            ':longitude' => $food['longitude'],
            ':area_en' => $food['area_en'],
            ':area_zh' => $food['area_zh'],
            ':area_pt' => $food['area_pt'],
            ':price_range' => $food['price_range'] ?? '$',
            ':rating' => $food['rating'] ?? 0,
        ]);

        $foodIdMap[$index + 1] = (int) $pdo->lastInsertId();
    }

    out('Inserted ' . count($foods) . ' foods.');

    $insertUser = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash, is_verified, locale) VALUES (:username, :email, :password_hash, :is_verified, :locale)'
    );

    $seedUsers = [
        ['username' => 'demo', 'email' => 'demo@example.com', 'locale' => 'zh'],
        ['username' => 'foodlover', 'email' => 'reviewer1@example.com', 'locale' => 'zh'],
        ['username' => 'macaufan', 'email' => 'reviewer2@example.com', 'locale' => 'zh'],
    ];

    foreach ($reviewUsers as $reviewUser) {
        $seedUsers[] = [
            'username' => $reviewUser['username'] ?? '',
            'email' => $reviewUser['email'] ?? '',
            'locale' => $reviewUser['locale'] ?? 'zh',
        ];
    }

    $userIds = [];
    $userIdByUsername = [];

    foreach ($seedUsers as $user) {
        if (($user['username'] ?? '') === '' || ($user['email'] ?? '') === '') {
            throw new RuntimeException('Seed users must define username and email.');
        }

        $insertUser->execute([
            ':username' => $user['username'],
            ':email' => $user['email'],
            ':password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            ':is_verified' => 1,
            ':locale' => $user['locale'],
        ]);

        $userId = (int) $pdo->lastInsertId();
        $userIds[] = $userId;
        $userIdByUsername[$user['username']] = $userId;
    }

    out('Inserted ' . count($userIds) . ' users.');

    $insertRestaurant = $pdo->prepare(
        'INSERT INTO restaurants (
            name_en,
            name_zh,
            name_pt,
            description_en,
            description_zh,
            description_pt,
            address_en,
            address_zh,
            address_pt,
            phone,
            opening_hours,
            price_range,
            google_rating,
            amap_rating,
            avg_rating,
            review_count,
            latitude,
            longitude,
            area_en,
            area_zh,
            area_pt,
            image_url
        ) VALUES (
            :name_en,
            :name_zh,
            :name_pt,
            :description_en,
            :description_zh,
            :description_pt,
            :address_en,
            :address_zh,
            :address_pt,
            :phone,
            :opening_hours,
            :price_range,
            :google_rating,
            :amap_rating,
            :avg_rating,
            :review_count,
            :latitude,
            :longitude,
            :area_en,
            :area_zh,
            :area_pt,
            :image_url
        )'
    );

    $insertFoodRestaurant = $pdo->prepare(
        'INSERT INTO food_restaurant (food_id, restaurant_id, is_signature) VALUES (:food_id, :restaurant_id, :is_signature)'
    );

    $restaurantMap = [];
    $restaurantFoodMap = [];

    foreach ($restaurants as $index => $restaurant) {
        $insertRestaurant->execute([
            ':name_en' => $restaurant['name_en'],
            ':name_zh' => $restaurant['name_zh'],
            ':name_pt' => $restaurant['name_pt'],
            ':description_en' => $restaurant['description_en'] ?? null,
            ':description_zh' => $restaurant['description_zh'] ?? null,
            ':description_pt' => $restaurant['description_pt'] ?? null,
            ':address_en' => $restaurant['address_en'] ?? null,
            ':address_zh' => $restaurant['address_zh'] ?? null,
            ':address_pt' => $restaurant['address_pt'] ?? null,
            ':phone' => $restaurant['phone'] ?? null,
            ':opening_hours' => $restaurant['opening_hours'] ?? null,
            ':price_range' => $restaurant['price_range'] ?? '$$',
            ':google_rating' => $restaurant['google_rating'] ?? null,
            ':amap_rating' => $restaurant['amap_rating'] ?? null,
            ':avg_rating' => $restaurant['avg_rating'] ?? $restaurant['overall_rating'] ?? 0,
            ':review_count' => $restaurant['review_count'] ?? 0,
            ':latitude' => $restaurant['latitude'],
            ':longitude' => $restaurant['longitude'],
            ':area_en' => $restaurant['area_en'] ?? null,
            ':area_zh' => $restaurant['area_zh'] ?? null,
            ':area_pt' => $restaurant['area_pt'] ?? null,
            ':image_url' => $restaurant['image_url'] ?? null,
        ]);

        $restaurantId = (int) $pdo->lastInsertId();
        $restaurantMap[$index + 1] = $restaurantId;
        $restaurantFoodMap[$restaurantId] = [];

        foreach (($restaurant['foods'] ?? []) as $mapping) {
            $sourceFoodId = (int) ($mapping['food_id'] ?? 0);

            if (!isset($foodIdMap[$sourceFoodId])) {
                throw new RuntimeException('Unknown food_id in restaurant seed: ' . $sourceFoodId);
            }

            $foodId = $foodIdMap[$sourceFoodId];

            $insertFoodRestaurant->execute([
                ':food_id' => $foodId,
                ':restaurant_id' => $restaurantId,
                ':is_signature' => !empty($mapping['is_signature']) ? 1 : 0,
            ]);

            $restaurantFoodMap[$restaurantId][] = $foodId;
        }
    }

    out('Inserted ' . count($restaurantMap) . ' restaurants.');

    $insertTag = $pdo->prepare(
        'INSERT INTO tags (name_en, name_zh, name_pt, slug) VALUES (:name_en, :name_zh, :name_pt, :slug)'
    );
    $tagMap = [];

    foreach ($tags as $tag) {
        $insertTag->execute([
            ':name_en' => $tag['name_en'],
            ':name_zh' => $tag['name_zh'],
            ':name_pt' => $tag['name_pt'],
            ':slug' => $tag['slug'],
        ]);

        $tagMap[$tag['slug']] = (int) $pdo->lastInsertId();
    }

    out('Inserted ' . count($tagMap) . ' tags.');

    $insertFoodTag = $pdo->prepare('INSERT INTO food_tags (food_id, tag_id) VALUES (:food_id, :tag_id)');

    foreach ($foodTags as $entry) {
        $sourceFoodId = (int) ($entry['food_id'] ?? 0);

        if (!isset($foodIdMap[$sourceFoodId])) {
            throw new RuntimeException('Unknown food_id in food_tags seed: ' . $sourceFoodId);
        }

        foreach (($entry['tag_slugs'] ?? []) as $tagSlug) {
            if (!isset($tagMap[$tagSlug])) {
                throw new RuntimeException('Unknown tag slug in food_tags seed: ' . $tagSlug);
            }

            $insertFoodTag->execute([
                ':food_id' => $foodIdMap[$sourceFoodId],
                ':tag_id' => $tagMap[$tagSlug],
            ]);
        }
    }

    out('Inserted food tag mappings.');

    $insertRestaurantTag = $pdo->prepare(
        'INSERT INTO restaurant_tags (restaurant_id, tag_id) VALUES (:restaurant_id, :tag_id)'
    );

    foreach ($restaurantTags as $entry) {
        $sourceRestaurantId = (int) ($entry['restaurant_id'] ?? 0);

        if (!isset($restaurantMap[$sourceRestaurantId])) {
            throw new RuntimeException('Unknown restaurant_id in restaurant_tags seed: ' . $sourceRestaurantId);
        }

        foreach (($entry['tag_slugs'] ?? []) as $tagSlug) {
            if (!isset($tagMap[$tagSlug])) {
                throw new RuntimeException('Unknown tag slug in restaurant_tags seed: ' . $tagSlug);
            }

            $insertRestaurantTag->execute([
                ':restaurant_id' => $restaurantMap[$sourceRestaurantId],
                ':tag_id' => $tagMap[$tagSlug],
            ]);
        }
    }

    out('Inserted restaurant tag mappings.');

    if ($seededReviews !== []) {
        $insertReview = $pdo->prepare(
            'INSERT INTO reviews (user_id, restaurant_id, food_id, rating, comment, created_at, updated_at)
             VALUES (:user_id, :restaurant_id, :food_id, :rating, :comment, :created_at, :updated_at)'
        );

        foreach ($seededReviews as $review) {
            $sourceRestaurantId = (int) ($review['restaurant_id'] ?? 0);

            if (!isset($restaurantMap[$sourceRestaurantId])) {
                throw new RuntimeException('Unknown restaurant_id in review seed: ' . $sourceRestaurantId);
            }

            $sourceFoodId = isset($review['food_id']) && $review['food_id'] !== null
                ? (int) $review['food_id']
                : null;

            if ($sourceFoodId !== null && !isset($foodIdMap[$sourceFoodId])) {
                throw new RuntimeException('Unknown food_id in review seed: ' . $sourceFoodId);
            }

            $username = (string) ($review['username'] ?? '');

            if (!isset($userIdByUsername[$username])) {
                throw new RuntimeException('Unknown username in review seed: ' . $username);
            }

            $comment = trim((string) ($review['comment'] ?? ''));

            if ($comment === '') {
                throw new RuntimeException('Review seed comment cannot be empty.');
            }

            $rating = max(1, min(5, (int) ($review['rating'] ?? 0)));
            $createdAt = (string) ($review['created_at'] ?? randomTimestamp(180));

            $insertReview->execute([
                ':user_id' => $userIdByUsername[$username],
                ':restaurant_id' => $restaurantMap[$sourceRestaurantId],
                ':food_id' => $sourceFoodId !== null ? $foodIdMap[$sourceFoodId] : null,
                ':rating' => $rating,
                ':comment' => $comment,
                ':created_at' => $createdAt,
                ':updated_at' => $createdAt,
            ]);
        }

        out('Inserted ' . count($seededReviews) . ' seeded reviews.');

        $pdo->exec(
            'UPDATE restaurants
             SET review_count = COALESCE((SELECT COUNT(*) FROM reviews WHERE restaurant_id = restaurants.id), 0)'
        );

        out('Updated restaurant review counts.');
    } elseif ($seedSampleReviews) {
    $insertReview = $pdo->prepare(
        'INSERT INTO reviews (user_id, restaurant_id, food_id, rating, comment, created_at, updated_at)
         VALUES (:user_id, :restaurant_id, :food_id, :rating, :comment, :created_at, :updated_at)'
    );

    $reviewComments = [
        3 => [
            '味道不錯，份量足夠，排隊也算值得。',
            '整體表現穩定，環境普通，但食物有水準。',
            '口味偏傳統，服務一般，還會再來試其他菜。',
        ],
        4 => [
            '招牌菜表現很好，味道夠地道，值得推薦。',
            '食物新鮮，口味層次豐富，是會想再回訪的店。',
            '位置方便，出品穩定，朋友來澳門我會帶他們來。',
            '比想像中更好吃，尤其招牌菜很有記憶點。',
        ],
        5 => [
            '非常有澳門味道，招牌菜幾乎沒有失手，必吃。',
            '這間真的名不虛傳，味道、氣氛和整體體驗都很滿意。',
            '每次帶朋友來都大受好評，是我心中的澳門名單前幾位。',
            '食物香氣和口感都很出色，吃完會想再排一次隊。',
            '料理很有特色，水準明顯高於一般遊客店。',
        ],
    ];

    $reviewCombos = [];

    foreach ($userIds as $userId) {
        foreach ($restaurantMap as $restaurantId) {
            $reviewCombos[] = ['user_id' => $userId, 'restaurant_id' => $restaurantId];
        }
    }

    shuffle($reviewCombos);
    $reviewCombos = array_slice($reviewCombos, 0, 30);

    foreach ($reviewCombos as $combo) {
        $rating = mt_rand(3, 5);
        $comments = $reviewComments[$rating];
        $comment = $comments[array_rand($comments)];
        $restaurantId = $combo['restaurant_id'];
        $servedFoods = $restaurantFoodMap[$restaurantId] ?? [];
        $foodId = [] !== $servedFoods && mt_rand(1, 100) <= 75
            ? $servedFoods[array_rand($servedFoods)]
            : null;
        $createdAt = randomTimestamp();

        $insertReview->execute([
            ':user_id' => $combo['user_id'],
            ':restaurant_id' => $restaurantId,
            ':food_id' => $foodId,
            ':rating' => $rating,
            ':comment' => $comment,
            ':created_at' => $createdAt,
            ':updated_at' => $createdAt,
        ]);
    }

    out('Inserted 30 sample reviews.');

    $pdo->exec(
        'UPDATE restaurants
         SET avg_rating = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE restaurant_id = restaurants.id), 0.0),
             review_count = COALESCE((SELECT COUNT(*) FROM reviews WHERE restaurant_id = restaurants.id), 0)'
    );

    out('Updated restaurant ratings.');
    } else {
        out('Skipped sample reviews; using seeded source ratings.');
    }

    $pdo->commit();
} catch (Throwable $throwable) {
    $pdo->rollBack();
    throw $throwable;
}

out('Database seeding completed successfully: ' . $databasePath);
