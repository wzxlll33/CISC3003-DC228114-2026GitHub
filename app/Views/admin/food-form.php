<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';
$food = is_array($food ?? null) ? $food : [];
$categories = is_array($categories ?? null) ? $categories : [];
$restaurants = is_array($restaurants ?? null) ? $restaurants : [];
$restaurantLinks = is_array($restaurantLinks ?? null) ? $restaurantLinks : [];
$restaurantContext = is_array($restaurantContext ?? null) ? $restaurantContext : null;
$restaurantContextId = $restaurantContext !== null ? (int) ($restaurantContext['id'] ?? 0) : 0;
$isRestaurantContext = $restaurantContextId > 0;
$session = isset($app) && is_object($app) && method_exists($app, 'session') ? $app->session() : null;
$oldFlash = $session ? $session->getFlash('old') : null;
$errorsFlash = $session ? $session->getFlash('errors') : null;
$old = is_array($oldFlash) ? $oldFlash : [];
$errors = is_array($errorsFlash) ? $errorsFlash : [];
$formAction = (string) ($formAction ?? '/admin/foods');
$mode = (string) ($mode ?? 'create');
$csrfService = isset($app) && is_object($app) && method_exists($app, 'csrf') ? $app->csrf() : null;
$contextBackHref = $isRestaurantContext ? '/admin/restaurants/' . $restaurantContextId . '/edit' : '/admin/foods';
$contextRestaurantName = $isRestaurantContext ? (string) ($restaurantContext['name_zh'] ?? $restaurantContext['name_en'] ?? '') : '';

$value = static function (string $field) use ($old, $food): string {
    if (array_key_exists($field, $old)) {
        return (string) $old[$field];
    }

    return (string) ($food[$field] ?? '');
};

$fieldError = static function (string $field) use ($errors): string {
    $messages = $errors[$field] ?? [];

    return is_array($messages) && isset($messages[0]) ? (string) $messages[0] : '';
};

$oldRestaurantIds = array_map('intval', is_array($old['restaurant_ids'] ?? null) ? $old['restaurant_ids'] : []);
$oldSignatureIds = array_map('intval', is_array($old['signature_restaurant_ids'] ?? null) ? $old['signature_restaurant_ids'] : []);
$hasOldRelations = $oldRestaurantIds !== [] || $oldSignatureIds !== [];

$isRestaurantChecked = static function (int $restaurantId) use ($hasOldRelations, $oldRestaurantIds, $restaurantLinks): bool {
    if ($hasOldRelations) {
        return in_array($restaurantId, $oldRestaurantIds, true);
    }

    return isset($restaurantLinks[$restaurantId]);
};

$isSignatureChecked = static function (int $restaurantId) use ($hasOldRelations, $oldSignatureIds, $restaurantLinks): bool {
    if ($hasOldRelations) {
        return in_array($restaurantId, $oldSignatureIds, true);
    }

    return !empty($restaurantLinks[$restaurantId]['is_signature']);
};

$isSignatureForContext = $isRestaurantContext
    ? (array_key_exists('is_signature_for_restaurant', $old)
        ? !empty($old['is_signature_for_restaurant'])
        : !empty($restaurantLinks[$restaurantContextId]['is_signature']))
    : false;
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
                    <h1 class="dashboard-title"><?= $mode === 'edit' ? '&#32534;&#36753;&#33756;&#24335;' : '&#26032;&#22686;&#33756;&#24335;' ?></h1>
                    <?php if ($isRestaurantContext): ?>
                        <p class="dashboard-lead">&#31649;&#29702; <?= $this->escape($contextRestaurantName) ?> &#30340;&#33756;&#24335;&#36164;&#26009;&#21644;&#25307;&#29260;&#29366;&#24577;&#12290;</p>
                    <?php else: ?>
                        <p class="dashboard-lead">&#32500;&#25252;&#33756;&#24335;&#21517;&#31216;&#12289;&#20998;&#31867;&#12289;&#22270;&#29255;&#12289;&#22320;&#22270;&#22352;&#26631;&#65292;&#20197;&#21450;&#23427;&#20250;&#20986;&#29616;&#22312;&#21738;&#20123;&#24215;&#38138;&#12290;</p>
                    <?php endif; ?>
                </div>
                <div class="dashboard-quick-actions">
                    <?php if ($mode === 'edit' && !empty($food['id'])): ?>
                        <a class="btn btn--outline btn--sm" href="/food/<?= (int) $food['id'] ?>">&#26597;&#30475;&#21069;&#21488;</a>
                    <?php endif; ?>
                    <a class="btn btn--outline btn--sm" href="<?= $this->escape($contextBackHref) ?>"><?= $isRestaurantContext ? '&#36820;&#22238;&#24215;&#38138;' : '&#36820;&#22238;&#21015;&#34920;' ?></a>
                </div>
            </header>

            <form class="admin-form dashboard-panel" method="post" action="<?= $this->escape($formAction) ?>">
                <?php if ($csrfService): ?>
                    <?= $csrfService->tokenField() ?>
                <?php endif; ?>
                <?php if ($isRestaurantContext): ?>
                    <input type="hidden" name="restaurant_context_id" value="<?= $restaurantContextId ?>">
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
                    <h2>&#20998;&#31867;&#19982;&#23637;&#31034;</h2>
                    <div class="admin-form__grid">
                        <label class="admin-field">
                            <span>&#20998;&#31867;</span>
                            <select name="category_id" required>
                                <option value="">&#35831;&#36873;&#25321;&#20998;&#31867;</option>
                                <?php foreach ($categories as $category): ?>
                                    <?php $categoryId = (int) ($category['id'] ?? 0); ?>
                                    <option value="<?= $categoryId ?>" <?= (int) $value('category_id') === $categoryId ? 'selected' : '' ?>>
                                        <?= $this->escape(trim(($category['icon'] ?? '') . ' ' . ($category['name_zh'] ?? $category['name_en'] ?? ''))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($fieldError('category_id') !== ''): ?>
                                <small><?= $this->escape($fieldError('category_id')) ?></small>
                            <?php endif; ?>
                        </label>
                        <label class="admin-field">
                            <span>&#20215;&#26684;</span>
                            <input name="price_range" value="<?= $this->escape($value('price_range') !== '' ? $value('price_range') : '$') ?>">
                        </label>
                        <label class="admin-field">
                            <span>&#35780;&#20998;</span>
                            <input type="number" step="0.1" min="0" max="5" name="rating" value="<?= $this->escape($value('rating') !== '' ? $value('rating') : '0') ?>">
                            <?php if ($fieldError('rating') !== ''): ?>
                                <small><?= $this->escape($fieldError('rating')) ?></small>
                            <?php endif; ?>
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
                            <input name="image_url" value="<?= $this->escape($value('image_url')) ?>" required>
                        </label>
                    </div>
                </section>

                <?php if ($isRestaurantContext): ?>
                    <section class="admin-form__section">
                        <h2>&#26412;&#24215;&#26174;&#31034;</h2>
                        <p class="admin-help">&#36825;&#36947;&#33756;&#20250;&#26174;&#31034;&#22312;&#24403;&#21069;&#24215;&#38138;&#30340;&#35814;&#24773;&#39029;&#12290;&#21487;&#22312;&#36825;&#37324;&#35774;&#32622;&#26159;&#21542;&#20316;&#20026;&#26412;&#24215;&#25307;&#29260;&#33756;&#12290;</p>
                        <label class="admin-relation-row admin-relation-row--single">
                            <input type="checkbox" name="is_signature_for_restaurant" value="1" <?= $isSignatureForContext ? 'checked' : '' ?>>
                            <span>&#20316;&#20026; <?= $this->escape($contextRestaurantName) ?> &#30340;&#25307;&#29260;&#33756;</span>
                        </label>
                    </section>
                <?php else: ?>
                    <section class="admin-form__section">
                        <h2>&#20851;&#32852;&#24215;&#38138;</h2>
                        <p class="admin-help">&#21246;&#36873;&#21518;&#65292;&#36825;&#36947;&#33756;&#20250;&#20986;&#29616;&#22312;&#23545;&#24212;&#39184;&#21381;&#30340;&#35814;&#24773;&#39029;&#12290;&#21491;&#20391;&#8220;&#25307;&#29260;&#8221;&#20915;&#23450;&#26159;&#21542;&#26174;&#31034;&#25307;&#29260;&#26631;&#31614;&#24182;&#20248;&#20808;&#25490;&#24207;&#12290;</p>
                        <div class="admin-relation-list">
                            <?php foreach ($restaurants as $restaurant): ?>
                                <?php
                                $restaurantId = (int) ($restaurant['id'] ?? 0);
                                $checked = $isRestaurantChecked($restaurantId);
                                $signatureChecked = $isSignatureChecked($restaurantId);
                                ?>
                                <div class="admin-relation-row">
                                    <label>
                                        <input type="checkbox" name="restaurant_ids[]" value="<?= $restaurantId ?>" <?= $checked ? 'checked' : '' ?>>
                                        <span><?= $this->escape($restaurant['name_zh'] ?? $restaurant['name_en'] ?? '') ?></span>
                                    </label>
                                    <label class="admin-relation-row__signature">
                                        <input type="checkbox" name="signature_restaurant_ids[]" value="<?= $restaurantId ?>" <?= $signatureChecked ? 'checked' : '' ?>>
                                        <span>&#25307;&#29260;</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <footer class="admin-form__actions">
                    <a class="btn btn--outline" href="<?= $this->escape($contextBackHref) ?>">&#21462;&#28040;</a>
                    <button type="submit" class="btn btn--primary"><?= $mode === 'edit' ? '&#20445;&#23384;&#20462;&#25913;' : '&#24314;&#31435;&#33756;&#24335;' ?></button>
                </footer>
            </form>
        </section>
    </main>
</div>
