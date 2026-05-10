<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\FoodRepository;
use App\Repositories\HistoryRepository;
use App\Repositories\UserRepository;
use App\Services\FoodService;
use PDO;

class DashboardController extends Controller
{
    private UserRepository $userRepository;

    private HistoryRepository $historyRepository;

    private FavoriteRepository $favoriteRepository;

    private FoodService $foodService;

    private ?array $currentUser = null;

    public function __construct($app)
    {
        parent::__construct($app);

        if (!$this->session->isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        $this->userRepository = new UserRepository($this->db);
        $this->historyRepository = new HistoryRepository($this->db);
        $this->favoriteRepository = new FavoriteRepository($this->db);
        $this->foodService = new FoodService(new FoodRepository($this->db));
    }

    public function index(): void
    {
        $user = $this->currentUser();
        $stats = $this->activityStats((int) $user['id']);

        $this->renderDashboard('dashboard/index', [
            'title' => 'Dashboard · Taste of Macau',
            'activePage' => 'overview',
            'user' => $user,
            'stats' => $stats,
            'recentSearches' => $this->formatSearchEntries($this->historyRepository->getSearchHistory((int) $user['id'], 5)),
            'recentBrowses' => $this->formatBrowseEntries($this->historyRepository->getBrowseHistory((int) $user['id'], 5)),
        ]);
    }

    public function profile(): void
    {
        $this->renderDashboard('dashboard/profile', [
            'title' => 'Profile · Taste of Macau',
            'activePage' => 'profile',
            'user' => $this->currentUser(),
        ]);
    }

    public function updateProfile(): void
    {
        if (!$this->validateCsrf('/dashboard/profile')) {
            return;
        }

        $user = $this->currentUser();
        $data = [
            'username' => trim((string) $this->request->post('username', '')),
            'email' => strtolower(trim((string) $this->request->post('email', ''))),
            'locale' => trim((string) $this->request->post('locale', 'zh')),
        ];

        $validation = $this->app->validator()->validate($data, [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
        ]);

        $errors = $validation['errors'];

        if (!in_array($data['locale'], ['zh', 'en', 'pt'], true)) {
            $errors['locale'][] = 'Please select a valid locale.';
        }

        if ($data['email'] !== strtolower((string) ($user['email'] ?? ''))) {
            $existing = $this->userRepository->findByEmail($data['email']);

            if (is_array($existing) && (int) ($existing['id'] ?? 0) !== (int) $user['id']) {
                $errors['email'][] = 'The email field has already been taken.';
            }
        }

        if ($errors !== []) {
            $this->flashFormState($errors, $data);
            $this->redirect('/dashboard/profile');
            return;
        }

        $changes = [];

        foreach (['username', 'email', 'locale'] as $field) {
            if (($user[$field] ?? null) !== $data[$field]) {
                $changes[$field] = $data[$field];
            }
        }

        if ($changes === []) {
            $this->session->set('username', $data['username']);
            $this->session->set('locale', $data['locale']);
            $this->session->flash('success', 'Your profile is already up to date.');
            $this->redirect('/dashboard/profile');
            return;
        }

        $updated = $this->userRepository->update((int) $user['id'], $changes);

        if (!$updated) {
            $this->session->flash('error', 'Unable to update your profile right now. Please try again.');
            $this->session->flash('old', $data);
            $this->redirect('/dashboard/profile');
            return;
        }

        $this->currentUser = array_merge($user, $changes);
        $this->session->set('username', $this->currentUser['username'] ?? $data['username']);
        $this->session->set('locale', $this->currentUser['locale'] ?? $data['locale']);
        $this->session->flash('success', 'Your profile has been updated successfully.');
        $this->redirect('/dashboard/profile');
    }

    public function searchHistory(): void
    {
        $user = $this->currentUser();
        $page = $this->resolvePage();
        $history = $this->paginatedSearchHistory((int) $user['id'], $page, 10);

        $this->renderDashboard('dashboard/search-history', [
            'title' => 'Search History · Taste of Macau',
            'activePage' => 'search-history',
            'user' => $user,
            'entries' => $this->formatSearchEntries($history['items']),
            'pagination' => $history['pagination'],
        ]);
    }

    public function browseHistory(): void
    {
        $user = $this->currentUser();
        $page = $this->resolvePage();
        $history = $this->paginatedBrowseHistory((int) $user['id'], $page, 9);

        $this->renderDashboard('dashboard/browse-history', [
            'title' => 'Browse History · Taste of Macau',
            'activePage' => 'browse-history',
            'user' => $user,
            'entries' => $this->formatBrowseEntries($history['items']),
            'pagination' => $history['pagination'],
        ]);
    }

    public function favorites(): void
    {
        $user = $this->currentUser();
        $locale = (string) ($user['locale'] ?? $this->resolveLocale());
        $favorites = array_map(function (array $entry) use ($locale): array {
            $food = $this->foodService->formatFood($entry, $locale);
            $food['is_favorited'] = true;
            $food['favorited_at'] = (string) ($entry['favorited_at'] ?? '');

            return $food;
        }, $this->favoriteRepository->getUserFavorites((int) $user['id']));

        $this->renderDashboard('dashboard/favorites', [
            'title' => 'Favorites · Taste of Macau',
            'activePage' => 'favorites',
            'user' => $user,
            'favorites' => $favorites,
        ]);
    }

    public function clearSearchHistory(): void
    {
        if (!$this->validateCsrf('/dashboard/search-history')) {
            return;
        }

        $deleted = $this->historyRepository->clearSearchHistory((int) $this->currentUser()['id']);
        $this->session->flash('success', $deleted > 0 ? 'Search history cleared.' : 'Search history was already empty.');
        $this->redirect('/dashboard/search-history');
    }

    public function clearBrowseHistory(): void
    {
        if (!$this->validateCsrf('/dashboard/browse-history')) {
            return;
        }

        $deleted = $this->historyRepository->clearBrowseHistory((int) $this->currentUser()['id']);
        $this->session->flash('success', $deleted > 0 ? 'Browse history cleared.' : 'Browse history was already empty.');
        $this->redirect('/dashboard/browse-history');
    }

    private function renderDashboard(string $template, array $data = []): void
    {
        $user = $data['user'] ?? $this->currentUser();
        $locale = (string) ($user['locale'] ?? $this->resolveLocale());

        $this->view($template, array_merge([
            'app' => $this->app,
            'user' => $user,
            'locale' => $locale,
            'csrfToken' => $this->csrf->getToken(),
            'extraScripts' => '<script src="/assets/js/dashboard.js"></script>',
        ], $data));
    }

    private function currentUser(): array
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $user = $this->userRepository->findById((int) $this->session->userId());

        if (!is_array($user)) {
            $this->session->destroy();
            $this->session->start();
            $this->session->flash('error', 'Please log in again to continue.');
            $this->redirect('/login');
            exit;
        }

        $user['locale'] = in_array((string) ($user['locale'] ?? 'zh'), ['zh', 'en', 'pt'], true)
            ? (string) $user['locale']
            : 'zh';

        $this->currentUser = $user;

        return $this->currentUser;
    }

    private function activityStats(int $userId): array
    {
        $searches = $this->db->fetch('SELECT COUNT(*) AS aggregate FROM search_history WHERE user_id = :user_id', [':user_id' => $userId]);

        return [
            'total_searches' => (int) ($searches['aggregate'] ?? 0),
            'total_browses' => $this->historyRepository->countBrowseHistory($userId),
            'total_favorites' => $this->favoriteRepository->count($userId),
            'member_since' => (string) ($this->currentUser()['created_at'] ?? ''),
        ];
    }

    private function paginatedSearchHistory(int $userId, int $page, int $perPage): array
    {
        $pdo = $this->db->pdo();
        $totalStatement = $pdo->prepare('SELECT COUNT(*) FROM search_history WHERE user_id = :user_id');
        $totalStatement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $totalStatement->execute();
        $total = (int) $totalStatement->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $totalPages);
        $offset = max(0, ($currentPage - 1) * $perPage);

        $statement = $pdo->prepare(
            'SELECT id, query, filters_json, results_count, created_at
             FROM search_history
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'pagination' => $this->pagination($currentPage, $perPage, $total, '/dashboard/search-history'),
        ];
    }

    private function paginatedBrowseHistory(int $userId, int $page, int $perPage): array
    {
        $total = $this->historyRepository->countBrowseHistory($userId);

        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $totalPages);
        $offset = max(0, ($currentPage - 1) * $perPage);

        return [
            'items' => $this->historyRepository->getPaginatedBrowseHistory($userId, $perPage, $offset),
            'pagination' => $this->pagination($currentPage, $perPage, $total, '/dashboard/browse-history'),
        ];
    }

    private function formatSearchEntries(array $entries): array
    {
        return array_map(static function (array $entry): array {
            $filters = self::decodeFilters($entry['filters_json'] ?? null);
            $params = ['q' => (string) ($entry['query'] ?? '')];

            if (isset($filters['category']) && is_string($filters['category']) && trim($filters['category']) !== '') {
                $params['category'] = trim($filters['category']);
            }

            if (isset($filters['service']) && is_string($filters['service']) && trim($filters['service']) !== '') {
                $params['service'] = trim($filters['service']);
            }

            return [
                'id' => (int) ($entry['id'] ?? 0),
                'query' => (string) ($entry['query'] ?? ''),
                'filters' => $filters,
                'results_count' => (int) ($entry['results_count'] ?? 0),
                'created_at' => (string) ($entry['created_at'] ?? ''),
                'rerun_url' => '/explore?' . http_build_query($params),
            ];
        }, $entries);
    }

    private function formatBrowseEntries(array $entries): array
    {
        $locale = (string) ($this->currentUser()['locale'] ?? $this->resolveLocale());

        return array_map(function (array $entry) use ($locale): array {
            if (($entry['item_type'] ?? 'food') === 'restaurant') {
                $restaurant = $this->formatBrowseRestaurant($entry, $locale);

                return [
                    'id' => (int) ($entry['id'] ?? 0),
                    'type' => 'restaurant',
                    'created_at' => (string) ($entry['created_at'] ?? ''),
                    'item' => $restaurant,
                    'restaurant' => $restaurant,
                ];
            }

            $food = $this->foodService->formatFood($entry, $locale);

            return [
                'id' => (int) ($entry['id'] ?? 0),
                'type' => 'food',
                'created_at' => (string) ($entry['created_at'] ?? ''),
                'item' => $food,
                'food' => $food,
            ];
        }, $entries);
    }

    private function formatBrowseRestaurant(array $entry, string $locale): array
    {
        $resolvedLocale = in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';

        return [
            'id' => (int) ($entry['restaurant_id'] ?? 0),
            'name' => $this->localizedValue($entry, 'name', $resolvedLocale),
            'description' => $this->localizedValue($entry, 'description', $resolvedLocale),
            'image_url' => (string) ($entry['image_url'] ?? ''),
            'area' => $this->localizedValue($entry, 'area', $resolvedLocale),
            'price_range' => (string) ($entry['price_range'] ?? ''),
            'rating' => isset($entry['avg_rating']) ? (float) $entry['avg_rating'] : (float) ($entry['rating'] ?? 0),
            'category_name' => $this->viewEngine->t('restaurantDetail.restaurant', $resolvedLocale),
            'detail_url' => '/restaurant/' . (int) ($entry['restaurant_id'] ?? 0),
        ];
    }

    private function localizedValue(array $row, string $prefix, string $locale): string
    {
        foreach ([$prefix . '_' . $locale, $prefix . '_zh', $prefix . '_en', $prefix . '_pt', $prefix] as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return '';
    }

    private function pagination(int $page, int $perPage, int $total, string $basePath): array
    {
        $totalPages = max(1, (int) ceil($total / $perPage));

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_url' => $page > 1 ? $basePath . '?page=' . ($page - 1) : null,
            'next_url' => $page < $totalPages ? $basePath . '?page=' . ($page + 1) : null,
        ];
    }

    private function resolvePage(): int
    {
        $page = filter_var($this->request->get('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $page === false ? 1 : (int) $page;
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }

    private function validateCsrf(string $redirectUrl): bool
    {
        $token = $this->request->post('_token') ?? $this->request->post('_csrf_token');

        if ($this->csrf->validateToken(is_string($token) ? $token : null)) {
            return true;
        }

        $this->session->flash('error', 'Your session has expired. Please try again.');
        $this->redirect($redirectUrl);

        return false;
    }

    private function flashFormState(array $errors, array $old = []): void
    {
        $this->session->flash('errors', $errors);

        if ($old !== []) {
            $this->session->flash('old', $old);
        }
    }

    private static function decodeFilters(mixed $filtersJson): array
    {
        if (!is_string($filtersJson) || trim($filtersJson) === '') {
            return [];
        }

        $decoded = json_decode($filtersJson, true);

        return is_array($decoded) ? $decoded : [];
    }
}
