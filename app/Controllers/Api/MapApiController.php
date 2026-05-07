<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Repositories\FoodRepository;
use App\Services\FoodService;

class MapApiController extends Controller
{
    private const MACAU_BOUNDS = [
        'min_lat' => 22.07,
        'max_lat' => 22.25,
        'min_lng' => 113.45,
        'max_lng' => 113.68,
    ];

    public function markers(): void
    {
        $locale = $this->resolveLocale();
        $categorySlug = trim((string) $this->request->get('category', ''));
        $foodService = new FoodService(new FoodRepository($this->db));

        $foods = $categorySlug !== ''
            ? $foodService->getFoodsByCategory($categorySlug, $locale)
            : $foodService->getAllFoods($locale);

        $markers = array_values(array_map(static function (array $food): array {
            return [
                'id' => (int) ($food['id'] ?? 0),
                'name' => (string) ($food['name'] ?? ''),
                'latitude' => isset($food['latitude']) ? (float) $food['latitude'] : null,
                'longitude' => isset($food['longitude']) ? (float) $food['longitude'] : null,
                'category_slug' => (string) ($food['category_slug'] ?? ''),
                'category_icon' => (string) ($food['category_icon'] ?? ''),
                'image_url' => (string) ($food['image_url'] ?? ''),
            ];
        }, array_filter($foods, static function (array $food): bool {
            return isset($food['latitude'], $food['longitude'])
                && is_numeric((string) $food['latitude'])
                && is_numeric((string) $food['longitude']);
        })));

        $this->json($markers);
    }

    public function geocode(): void
    {
        $query = trim((string) $this->request->get('q', ''));

        if ($query === '') {
            $this->json(['error' => 'Search query is required.'], 422);
            return;
        }

        $locale = $this->resolveLocale();
        $results = $this->fetchGeocodeResults($this->withMacauHint($query, $locale), $locale);

        if ($results === null) {
            $this->json(['error' => 'Map location search is unavailable.'], 502);
            return;
        }

        $macauResults = array_values(array_filter($results, fn (array $result): bool => $this->isInsideMacauBounds(
            (float) $result['latitude'],
            (float) $result['longitude']
        )));

        $this->json([
            'results' => $macauResults,
        ]);
    }

    private function fetchGeocodeResults(string $query, string $locale): ?array
    {
        $params = http_build_query([
            'format' => 'jsonv2',
            'q' => $query,
            'limit' => 5,
            'addressdetails' => 0,
            'accept-language' => $this->acceptLanguage($locale),
            'viewbox' => '113.45,22.25,113.68,22.07',
            'bounded' => 0,
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: TasteOfMacau-CISC3003/1.0\r\nAccept: application/json\r\n",
                'timeout' => 5,
            ],
        ]);
        $body = @file_get_contents('https://nominatim.openstreetmap.org/search?' . $params, false, $context);

        if ($body === false) {
            return null;
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return null;
        }

        $results = [];

        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['lat'], $item['lon'])) {
                continue;
            }

            $lat = filter_var($item['lat'], FILTER_VALIDATE_FLOAT);
            $lng = filter_var($item['lon'], FILTER_VALIDATE_FLOAT);

            if ($lat === false || $lng === false) {
                continue;
            }

            $results[] = [
                'name' => (string) ($item['name'] ?? $item['display_name'] ?? ''),
                'display_name' => (string) ($item['display_name'] ?? $item['name'] ?? ''),
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'category' => (string) ($item['category'] ?? ''),
                'type' => (string) ($item['type'] ?? ''),
            ];
        }

        return $results;
    }

    private function withMacauHint(string $query, string $locale): string
    {
        if (preg_match('/macau|macao/i', $query) || str_contains($query, '澳門') || str_contains($query, '澳门')) {
            return $query;
        }

        return match ($locale) {
            'pt' => $query . ' Macau',
            'en' => $query . ' Macau',
            default => $query . ' 澳門',
        };
    }

    private function acceptLanguage(string $locale): string
    {
        return match ($locale) {
            'pt' => 'pt,en,zh',
            'en' => 'en,zh,pt',
            default => 'zh-Hant,zh,en,pt',
        };
    }

    private function isInsideMacauBounds(float $lat, float $lng): bool
    {
        return $lat >= self::MACAU_BOUNDS['min_lat']
            && $lat <= self::MACAU_BOUNDS['max_lat']
            && $lng >= self::MACAU_BOUNDS['min_lng']
            && $lng <= self::MACAU_BOUNDS['max_lng'];
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
