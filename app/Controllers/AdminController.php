<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FeedbackRepository;
use App\Repositories\FoodRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\UserRepository;

class AdminController extends Controller
{
    private const ADMIN_EMAILS = ['demo@example.com'];

    private UserRepository $userRepository;

    private RestaurantRepository $restaurantRepository;

    private FoodRepository $foodRepository;

    private FeedbackRepository $feedbackRepository;

    private array $currentUser;

    public function __construct($app)
    {
        parent::__construct($app);

        if (!$this->session->isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        $this->userRepository = new UserRepository($this->db);
        $user = $this->userRepository->findById((int) $this->session->userId());

        if (!is_array($user) || !in_array(strtolower((string) ($user['email'] ?? '')), self::ADMIN_EMAILS, true)) {
            $this->response->html('<h1>403 Forbidden</h1>', 403);
            exit;
        }

        $this->currentUser = $user;
        $this->restaurantRepository = new RestaurantRepository($this->db);
        $this->foodRepository = new FoodRepository($this->db);
        $this->feedbackRepository = new FeedbackRepository($this->db);
    }

    public function index(): void
    {
        $this->redirect('/admin/restaurants');
    }

    public function foods(): void
    {
        $this->redirect('/admin/restaurants');
    }

    public function createFood(): void
    {
        $this->renderFoodForm([], 'create', '/admin/foods');
    }

    public function storeFood(): void
    {
        $restaurantId = $this->restaurantContextId();
        $formPath = $restaurantId !== null ? $this->restaurantFoodCreatePath($restaurantId) : '/admin/foods/create';

        if (!$this->validateCsrf($formPath)) {
            return;
        }

        $data = $this->foodData();
        $errors = $this->validateFoodData($data);

        if ($errors !== []) {
            $this->flashFormState($errors, $data);
            $this->redirect($formPath);
            return;
        }

        $foodId = $this->foodRepository->create($this->normalizedFoodData($data));
        $this->syncFoodLinksForContext($foodId, $restaurantId);
        $this->session->flash('success', 'Food created.');
        $this->redirect($restaurantId !== null ? $this->restaurantEditPath($restaurantId) : '/admin/foods/' . $foodId . '/edit');
    }

    public function editFood(string $id): void
    {
        $foodId = $this->foodId($id);

        if ($foodId === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        $food = $this->foodRepository->getById($foodId);

        if ($food === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        $this->renderFoodForm($food, 'edit', '/admin/foods/' . $foodId);
    }

    public function updateFood(string $id): void
    {
        $foodId = $this->foodId($id);

        if ($foodId === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        $restaurantId = $this->restaurantContextId();
        $formPath = $restaurantId !== null ? $this->restaurantFoodEditPath($restaurantId, $foodId) : '/admin/foods/' . $foodId . '/edit';

        if ($restaurantId !== null && $this->foodRepository->getForRestaurant($foodId, $restaurantId) === null) {
            $this->response->html('<h1>Food not found for this restaurant</h1>', 404);
            return;
        }

        if (!$this->validateCsrf($formPath)) {
            return;
        }

        $data = $this->foodData();
        $errors = $this->validateFoodData($data);

        if ($errors !== []) {
            $this->flashFormState($errors, $data);
            $this->redirect($formPath);
            return;
        }

        $this->foodRepository->update($foodId, $this->normalizedFoodData($data));
        $this->syncFoodLinksForContext($foodId, $restaurantId);
        $this->session->flash('success', 'Food updated.');
        $this->redirect($restaurantId !== null ? $this->restaurantEditPath($restaurantId) : '/admin/foods/' . $foodId . '/edit');
    }

    public function deleteFood(string $id): void
    {
        $foodId = $this->foodId($id);

        if ($foodId === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        if (!$this->validateCsrf('/admin/foods')) {
            return;
        }

        $this->foodRepository->delete($foodId);
        $this->session->flash('success', 'Food deleted.');
        $this->redirect('/admin/foods');
    }

    public function createRestaurantFood(string $restaurantId): void
    {
        $restaurant = $this->restaurantForAdmin($restaurantId);

        if ($restaurant === null) {
            return;
        }

        $id = (int) $restaurant['id'];
        $this->renderFoodForm(
            [],
            'create',
            '/admin/restaurants/' . $id . '/foods',
            $restaurant
        );
    }

    public function storeRestaurantFood(string $restaurantId): void
    {
        $restaurant = $this->restaurantForAdmin($restaurantId);

        if ($restaurant === null) {
            return;
        }

        $_POST['restaurant_context_id'] = (string) (int) $restaurant['id'];
        $this->storeFood();
    }

    public function editRestaurantFood(string $restaurantId, string $foodId): void
    {
        $restaurant = $this->restaurantForAdmin($restaurantId);
        $id = $this->foodId($foodId);

        if ($restaurant === null) {
            return;
        }

        if ($id === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        $food = $this->foodRepository->getForRestaurant($id, (int) $restaurant['id']);

        if ($food === null) {
            $this->response->html('<h1>Food not found for this restaurant</h1>', 404);
            return;
        }

        $this->renderFoodForm(
            $food,
            'edit',
            '/admin/restaurants/' . (int) $restaurant['id'] . '/foods/' . $id,
            $restaurant
        );
    }

    public function updateRestaurantFood(string $restaurantId, string $foodId): void
    {
        $restaurant = $this->restaurantForAdmin($restaurantId);

        if ($restaurant === null) {
            return;
        }

        $_POST['restaurant_context_id'] = (string) (int) $restaurant['id'];
        $this->updateFood($foodId);
    }

    public function removeRestaurantFood(string $restaurantId, string $foodId): void
    {
        $restaurant = $this->restaurantForAdmin($restaurantId);
        $id = $this->foodId($foodId);

        if ($restaurant === null) {
            return;
        }

        $restaurantIdInt = (int) $restaurant['id'];

        if ($id === null) {
            $this->response->html('<h1>Food not found</h1>', 404);
            return;
        }

        if (!$this->validateCsrf($this->restaurantEditPath($restaurantIdInt))) {
            return;
        }

        $this->foodRepository->removeRestaurantLink($id, $restaurantIdInt);
        $this->session->flash('success', 'Food removed from this restaurant.');
        $this->redirect($this->restaurantEditPath($restaurantIdInt));
    }

    public function restaurants(): void
    {
        $this->renderAdmin('admin/restaurants', [
            'title' => 'Admin restaurants - Taste of Macau',
            'activePage' => 'admin-restaurants',
            'restaurants' => $this->restaurantRepository->getAll(),
        ]);
    }

    public function createRestaurant(): void
    {
        $this->renderAdmin('admin/restaurant-form', [
            'title' => 'Add restaurant - Taste of Macau',
            'activePage' => 'admin-restaurants',
            'restaurant' => [],
            'formAction' => '/admin/restaurants',
            'mode' => 'create',
        ]);
    }

    public function storeRestaurant(): void
    {
        if (!$this->validateCsrf('/admin/restaurants/create')) {
            return;
        }

        $data = $this->restaurantData();
        $errors = $this->validateRestaurantData($data);

        if ($errors !== []) {
            $this->flashFormState($errors, $data);
            $this->redirect('/admin/restaurants/create');
            return;
        }

        $restaurantId = $this->restaurantRepository->create($this->normalizedRestaurantData($data));
        $this->session->flash('success', 'Restaurant created.');
        $this->redirect('/admin/restaurants/' . $restaurantId . '/edit');
    }

    public function editRestaurant(string $id): void
    {
        $restaurantId = $this->restaurantId($id);

        if ($restaurantId === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return;
        }

        $restaurant = $this->restaurantRepository->getById($restaurantId);

        if ($restaurant === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return;
        }

        $this->renderAdmin('admin/restaurant-form', [
            'title' => 'Edit restaurant - Taste of Macau',
            'activePage' => 'admin-restaurants',
            'restaurant' => $restaurant,
            'restaurantFoods' => $this->foodRepository->getByRestaurant($restaurantId),
            'formAction' => '/admin/restaurants/' . $restaurantId,
            'mode' => 'edit',
        ]);
    }

    public function updateRestaurant(string $id): void
    {
        $restaurantId = $this->restaurantId($id);

        if ($restaurantId === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return;
        }

        if (!$this->validateCsrf('/admin/restaurants/' . $restaurantId . '/edit')) {
            return;
        }

        $data = $this->restaurantData();
        $errors = $this->validateRestaurantData($data);

        if ($errors !== []) {
            $this->flashFormState($errors, $data);
            $this->redirect('/admin/restaurants/' . $restaurantId . '/edit');
            return;
        }

        $this->restaurantRepository->update($restaurantId, $this->normalizedRestaurantData($data));
        $this->session->flash('success', 'Restaurant updated.');
        $this->redirect('/admin/restaurants/' . $restaurantId . '/edit');
    }

    public function deleteRestaurant(string $id): void
    {
        $restaurantId = $this->restaurantId($id);

        if ($restaurantId === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return;
        }

        if (!$this->validateCsrf('/admin/restaurants')) {
            return;
        }

        $this->restaurantRepository->delete($restaurantId);
        $this->session->flash('success', 'Restaurant deleted.');
        $this->redirect('/admin/restaurants');
    }

    public function feedback(): void
    {
        $this->renderAdmin('admin/feedback', [
            'title' => 'Feedback - Taste of Macau',
            'activePage' => 'admin-feedback',
            'feedbackReports' => $this->feedbackRepository->getAll(),
            'statusCounts' => $this->feedbackRepository->countByStatus(),
        ]);
    }

    public function updateFeedbackStatus(string $id): void
    {
        $feedbackId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($feedbackId === false) {
            $this->response->html('<h1>Feedback not found</h1>', 404);
            return;
        }

        if (!$this->validateCsrf('/admin/feedback')) {
            return;
        }

        $status = (string) $this->request->post('status', 'new');

        if (!in_array($status, ['new', 'reviewing', 'resolved'], true)) {
            $status = 'new';
        }

        $this->feedbackRepository->updateStatus((int) $feedbackId, $status);
        $this->session->flash('success', 'Feedback status updated.');
        $this->redirect('/admin/feedback');
    }

    private function renderAdmin(string $template, array $data = []): void
    {
        $locale = $this->resolveLocale();

        $this->view($template, array_merge([
            'app' => $this->app,
            'user' => $this->currentUser,
            'locale' => $locale,
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-dashboard',
        ], $data));
    }

    private function renderFoodForm(array $food, string $mode, string $formAction, ?array $restaurantContext = null): void
    {
        $foodId = (int) ($food['id'] ?? 0);

        $this->renderAdmin('admin/food-form', [
            'title' => ($mode === 'edit' ? 'Edit food' : 'Add food') . ' - Taste of Macau',
            'activePage' => $restaurantContext !== null ? 'admin-restaurants' : 'admin-foods',
            'food' => $food,
            'categories' => $this->foodRepository->getCategories(),
            'restaurants' => $this->restaurantRepository->getAll(),
            'restaurantLinks' => $foodId > 0 ? $this->foodRepository->getRestaurantLinks($foodId) : [],
            'restaurantContext' => $restaurantContext,
            'formAction' => $formAction,
            'mode' => $mode,
        ]);
    }

    private function restaurantData(): array
    {
        $fields = [
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
            'area_en',
            'area_zh',
            'area_pt',
            'image_url',
        ];
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = trim((string) $this->request->post($field, ''));
        }

        $data['latitude'] = trim((string) $this->request->post('latitude', ''));
        $data['longitude'] = trim((string) $this->request->post('longitude', ''));

        return $data;
    }

    private function normalizedRestaurantData(array $data): array
    {
        $data['latitude'] = (float) ($data['latitude'] ?? 0);
        $data['longitude'] = (float) ($data['longitude'] ?? 0);

        return $data;
    }

    private function foodData(): array
    {
        $fields = [
            'name_en',
            'name_zh',
            'name_pt',
            'description_en',
            'description_zh',
            'description_pt',
            'image_url',
            'area_en',
            'area_zh',
            'area_pt',
            'price_range',
        ];
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = trim((string) $this->request->post($field, ''));
        }

        $data['category_id'] = trim((string) $this->request->post('category_id', ''));
        $data['latitude'] = trim((string) $this->request->post('latitude', ''));
        $data['longitude'] = trim((string) $this->request->post('longitude', ''));
        $data['rating'] = trim((string) $this->request->post('rating', '0'));

        return $data;
    }

    private function normalizedFoodData(array $data): array
    {
        $data['category_id'] = (int) ($data['category_id'] ?? 0);
        $data['latitude'] = (float) ($data['latitude'] ?? 0);
        $data['longitude'] = (float) ($data['longitude'] ?? 0);
        $data['rating'] = (float) ($data['rating'] ?? 0);

        return $data;
    }

    private function validateFoodData(array $data): array
    {
        $errors = [];

        foreach (['name_zh', 'name_en', 'name_pt'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field][] = 'Name is required.';
            }
        }

        $categoryId = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($categoryId === false) {
            $errors['category_id'][] = 'Category is required.';
        } else {
            $categoryExists = false;

            foreach ($this->foodRepository->getCategories() as $category) {
                if ((int) ($category['id'] ?? 0) === (int) $categoryId) {
                    $categoryExists = true;
                    break;
                }
            }

            if (!$categoryExists) {
                $errors['category_id'][] = 'Category is invalid.';
            }
        }

        foreach (['latitude', 'longitude'] as $field) {
            if (!is_numeric($data[$field] ?? null)) {
                $errors[$field][] = 'Coordinate is required.';
            }
        }

        if (!is_numeric($data['rating'] ?? null)) {
            $errors['rating'][] = 'Rating must be a number.';
        }

        if (trim((string) ($data['image_url'] ?? '')) === '') {
            $errors['image_url'][] = 'Image URL is required.';
        }

        return $errors;
    }

    private function foodRestaurantLinks(): array
    {
        $restaurantIds = $this->request->post('restaurant_ids', []);
        $signatureIds = $this->request->post('signature_restaurant_ids', []);

        if (!is_array($restaurantIds)) {
            $restaurantIds = [];
        }

        if (!is_array($signatureIds)) {
            $signatureIds = [];
        }

        $signatureLookup = array_fill_keys(array_map('intval', $signatureIds), true);
        $links = [];

        foreach (array_unique(array_map('intval', $restaurantIds)) as $restaurantId) {
            if ($restaurantId <= 0) {
                continue;
            }

            $links[] = [
                'restaurant_id' => $restaurantId,
                'is_signature' => isset($signatureLookup[$restaurantId]),
            ];
        }

        return $links;
    }

    private function syncFoodLinksForContext(int $foodId, ?int $restaurantId): void
    {
        if ($restaurantId !== null) {
            $this->foodRepository->upsertRestaurantLink(
                $foodId,
                $restaurantId,
                (bool) $this->request->post('is_signature_for_restaurant', false)
            );
            return;
        }

        $this->foodRepository->syncRestaurantLinks($foodId, $this->foodRestaurantLinks());
    }

    private function restaurantContextId(): ?int
    {
        $restaurantId = $this->request->post('restaurant_context_id', null);

        if ($restaurantId === null || $restaurantId === '') {
            return null;
        }

        $id = filter_var($restaurantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : (int) $id;
    }

    private function restaurantForAdmin(string $id): ?array
    {
        $restaurantId = $this->restaurantId($id);

        if ($restaurantId === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return null;
        }

        $restaurant = $this->restaurantRepository->getById($restaurantId);

        if ($restaurant === null) {
            $this->response->html('<h1>Restaurant not found</h1>', 404);
            return null;
        }

        return $restaurant;
    }

    private function restaurantEditPath(int $restaurantId): string
    {
        return '/admin/restaurants/' . $restaurantId . '/edit';
    }

    private function restaurantFoodCreatePath(int $restaurantId): string
    {
        return '/admin/restaurants/' . $restaurantId . '/foods/create';
    }

    private function restaurantFoodEditPath(int $restaurantId, int $foodId): string
    {
        return '/admin/restaurants/' . $restaurantId . '/foods/' . $foodId . '/edit';
    }

    private function validateRestaurantData(array $data): array
    {
        $errors = [];

        foreach (['name_zh', 'name_en', 'name_pt'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field][] = 'Name is required.';
            }
        }

        foreach (['latitude', 'longitude'] as $field) {
            if (!is_numeric($data[$field] ?? null)) {
                $errors[$field][] = 'Coordinate is required.';
            }
        }

        return $errors;
    }

    private function restaurantId(string $id): ?int
    {
        $restaurantId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $restaurantId === false ? null : (int) $restaurantId;
    }

    private function foodId(string $id): ?int
    {
        $foodId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $foodId === false ? null : (int) $foodId;
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

    private function flashFormState(array $errors, array $old): void
    {
        $this->session->flash('errors', $errors);
        $this->session->flash('old', $old);
    }

    private function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
