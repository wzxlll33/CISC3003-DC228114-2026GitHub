<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';
$restaurant = is_array($restaurant ?? null) ? $restaurant : [];
$restaurantFoods = is_array($restaurantFoods ?? null) ? $restaurantFoods : [];
$session = isset($app) && is_object($app) && method_exists($app, 'session') ? $app->session() : null;
$oldFlash = $session ? $session->getFlash('old') : null;
$errorsFlash = $session ? $session->getFlash('errors') : null;
$old = is_array($oldFlash) ? $oldFlash : [];
$errors = is_array($errorsFlash) ? $errorsFlash : [];
$formAction = (string) ($formAction ?? '/admin/restaurants');
$mode = (string) ($mode ?? 'create');
$csrfService = isset($app) && is_object($app) && method_exists($app, 'csrf') ? $app->csrf() : null;

$value = static function (string $field) use ($old, $restaurant): string {
    if (array_key_exists($field, $old)) {
        return (string) $old[$field];
    }

    return (string) ($restaurant[$field] ?? '');
};

$fieldError = static function (string $field) use ($errors): string {
    $messages = $errors[$field] ?? [];

    return is_array($messages) && isset($messages[0]) ? (string) $messages[0] : '';
};
?>

<div class="dashboard dashboard--street-sign admin-dashboard">
    <aside class="dashboard__sidebar">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-sidebar.php'; ?>
    </aside>

    <main class="dashboard__content">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-content-nav.php'; ?>

        <section class="dashboard-surface admin-panel">
            <?php include ROOT_PATH . '/app/Views/partials/flash-message.php'; ?>

            <header class="dashboard-hero admin-hero">
                <div>
                    <span class="dashboard-eyebrow">Admin</span>
                    <h1 class="dashboard-title"><?= $mode === 'edit' ? '&#32534;&#36753;&#24215;&#38138;' : '&#26032;&#22686;&#24215;&#38138;' ?></h1>
                    <p class="dashboard-lead">&#32500;&#25252;&#19977;&#35821;&#21517;&#31216;&#12289;&#22320;&#22336;&#12289;&#33829;&#19994;&#26102;&#38388;&#21644;&#22320;&#22270;&#22352;&#26631;&#12290;</p>
                </div>
                <div class="dashboard-quick-actions">
                    <?php if ($mode === 'edit' && !empty($restaurant['id'])): ?>
                        <a class="btn btn--outline btn--sm" href="/restaurant/<?= (int) $restaurant['id'] ?>">&#26597;&#30475;&#21069;&#21488;</a>
                    <?php endif; ?>
                    <a class="btn btn--outline btn--sm" href="/admin/restaurants">&#36820;&#22238;&#21015;&#34920;</a>
                </div>
            </header>

            <form class="admin-form dashboard-panel" method="post" action="<?= $this->escape($formAction) ?>">
                <?php if ($csrfService): ?>
                    <?= $csrfService->tokenField() ?>
                <?php endif; ?>

                <section class="admin-form__section">
                    <h2>&#22522;&#26412;&#36164;&#26009;</h2>
                    <div class="admin-form__grid admin-form__grid--three">
                        <?php foreach (['name_zh' => '&#20013;&#25991;&#21517;&#31216;', 'name_en' => 'English name', 'name_pt' => 'Nome PT'] as $field => $label): ?>
                            <label class="admin-field">
                                <span><?= $label ?></span>
                                <input name="<?= $this->escape($field) ?>" value="<?= $this->escape($value($field)) ?>" required>
                                <?php if ($fieldError($field) !== ''): ?>
                                    <small><?= $this->escape($fieldError($field)) ?></small>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-form__grid admin-form__grid--three">
                        <?php foreach (['description_zh' => '&#20013;&#25991;&#31616;&#20171;', 'description_en' => 'English description', 'description_pt' => 'Descricao PT'] as $field => $label): ?>
                            <label class="admin-field">
                                <span><?= $label ?></span>
                                <textarea name="<?= $this->escape($field) ?>" rows="4"><?= $this->escape($value($field)) ?></textarea>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-form__section">
                    <h2>&#20301;&#32622;&#19982;&#32852;&#31995;</h2>
                    <div class="admin-form__grid admin-form__grid--three">
                        <?php foreach (['address_zh' => '&#20013;&#25991;&#22320;&#22336;', 'address_en' => 'English address', 'address_pt' => 'Endereco PT'] as $field => $label): ?>
                            <label class="admin-field">
                                <span><?= $label ?></span>
                                <input name="<?= $this->escape($field) ?>" value="<?= $this->escape($value($field)) ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-form__grid">
                        <label class="admin-field">
                            <span>&#30005;&#35805;</span>
                            <input name="phone" value="<?= $this->escape($value('phone')) ?>">
                        </label>
                        <label class="admin-field">
                            <span>&#33829;&#19994;&#26102;&#38388;</span>
                            <input name="opening_hours" value="<?= $this->escape($value('opening_hours')) ?>">
                        </label>
                        <label class="admin-field">
                            <span>&#20215;&#26684;</span>
                            <input name="price_range" value="<?= $this->escape($value('price_range') !== '' ? $value('price_range') : '$$') ?>">
                        </label>
                    </div>

                    <div class="admin-form__grid admin-form__grid--three">
                        <?php foreach (['area_zh' => '&#20013;&#25991;&#21306;&#22495;', 'area_en' => 'English area', 'area_pt' => 'Zona PT'] as $field => $label): ?>
                            <label class="admin-field">
                                <span><?= $label ?></span>
                                <input name="<?= $this->escape($field) ?>" value="<?= $this->escape($value($field)) ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-form__grid">
                        <label class="admin-field">
                            <span>&#32428;&#24230;</span>
                            <input type="number" step="0.000001" name="latitude" value="<?= $this->escape($value('latitude')) ?>" required>
                            <?php if ($fieldError('latitude') !== ''): ?>
                                <small><?= $this->escape($fieldError('latitude')) ?></small>
                            <?php endif; ?>
                        </label>
                        <label class="admin-field">
                            <span>&#32463;&#24230;</span>
                            <input type="number" step="0.000001" name="longitude" value="<?= $this->escape($value('longitude')) ?>" required>
                            <?php if ($fieldError('longitude') !== ''): ?>
                                <small><?= $this->escape($fieldError('longitude')) ?></small>
                            <?php endif; ?>
                        </label>
                        <label class="admin-field">
                            <span>&#22270;&#29255; URL</span>
                            <input name="image_url" value="<?= $this->escape($value('image_url')) ?>">
                        </label>
                    </div>
                </section>

                <footer class="admin-form__actions">
                    <a class="btn btn--outline" href="/admin/restaurants">&#21462;&#28040;</a>
                    <button type="submit" class="btn btn--primary"><?= $mode === 'edit' ? '&#20445;&#23384;&#20462;&#25913;' : '&#24314;&#31435;&#24215;&#38138;' ?></button>
                </footer>
            </form>

            <?php if ($mode === 'edit' && !empty($restaurant['id'])): ?>
                <?php $restaurantId = (int) $restaurant['id']; ?>
                <section id="restaurant-foods" class="dashboard-panel admin-form__section admin-restaurant-foods">
                    <div class="admin-section-head">
                        <div>
                            <h2>&#26412;&#24215;&#33756;&#24335;</h2>
                            <p class="admin-help">&#22312;&#24403;&#21069;&#24215;&#38138;&#20869;&#26032;&#22686;&#12289;&#32534;&#36753;&#25110;&#31227;&#38500;&#33756;&#24335;&#12290;&#31227;&#38500;&#21482;&#20250;&#21462;&#28040;&#36825;&#23478;&#24215;&#30340;&#20851;&#32852;&#65292;&#19981;&#20250;&#21024;&#38500;&#33756;&#24335;&#26412;&#36523;&#12290;</p>
                        </div>
                        <a class="btn btn--primary btn--sm" href="/admin/restaurants/<?= $restaurantId ?>/foods/create">&#26032;&#22686;&#26412;&#24215;&#33756;&#24335;</a>
                    </div>

                    <?php if ($restaurantFoods === []): ?>
                        <p class="admin-empty">&#36825;&#23478;&#24215;&#36824;&#27809;&#26377;&#20851;&#32852;&#33756;&#24335;&#12290;</p>
                    <?php else: ?>
                        <div class="admin-food-list">
                            <?php foreach ($restaurantFoods as $food): ?>
                                <?php $foodId = (int) ($food['id'] ?? 0); ?>
                                <article class="admin-food-row">
                                    <img class="admin-food-row__image" src="<?= $this->escape($food['image_url'] ?? '') ?>" alt="<?= $this->escape($food['name_zh'] ?? $food['name_en'] ?? '') ?>">
                                    <div class="admin-food-row__body">
                                        <div class="admin-food-row__title">
                                            <strong><?= $this->escape($food['name_zh'] ?? $food['name_en'] ?? '') ?></strong>
                                            <?php if (!empty($food['is_signature'])): ?>
                                                <span class="admin-status admin-status--resolved">&#25307;&#29260;</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="admin-muted">
                                            <?= $this->escape(trim(($food['category_icon'] ?? '') . ' ' . ($food['category_name_zh'] ?? $food['category_name_en'] ?? ''))) ?>
                                            &middot; <?= $this->escape($food['price_range'] ?? '') ?>
                                            &middot; <?= $this->escape(number_format((float) ($food['rating'] ?? 0), 1)) ?>
                                        </div>
                                        <p><?= $this->escape($food['description_zh'] ?? $food['description_en'] ?? '') ?></p>
                                    </div>
                                    <div class="admin-actions admin-food-row__actions">
                                        <a class="btn btn--outline btn--sm" href="/food/<?= $foodId ?>">&#21069;&#21488;</a>
                                        <a class="btn btn--outline btn--sm" href="/admin/restaurants/<?= $restaurantId ?>/foods/<?= $foodId ?>/edit">&#32534;&#36753;</a>
                                        <form method="post" action="/admin/restaurants/<?= $restaurantId ?>/foods/<?= $foodId ?>/remove" onsubmit="return confirm('Remove this dish from this restaurant?');">
                                            <?php if ($csrfService): ?>
                                                <?= $csrfService->tokenField() ?>
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn--outline btn--sm">&#31227;&#38500;</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
    </main>
</div>
