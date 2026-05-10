<?php
$layout = 'layouts/main';

$leafletCss = is_file(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.css') ? file_get_contents(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.css') : '';
$extraHead = '<style>' . "\n" . $leafletCss . "\n" . '</style>';

$leafletScript = is_file(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.js') ? file_get_contents(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.js') : '';
$mapScript = is_file(ROOT_PATH . '/public/assets/js/map.js') ? file_get_contents(ROOT_PATH . '/public/assets/js/map.js') : '';
$extraScripts = '<script>' . "\n" . $leafletScript . "\n" . '</script>' . "\n"
    . '<script>' . "\n" . $mapScript . "\n" . '</script>';

$food = is_array($food ?? null) ? $food : [];
$foodId = (int) ($food['id'] ?? 0);
$rating = (float) ($food['rating'] ?? 0);
$filledStars = max(0, min(5, (int) round($rating)));
$stars = str_repeat("\u{2605}", $filledStars) . str_repeat("\u{2606}", 5 - $filledStars);
$isFavorited = !empty($food['is_favorited']);
$favoriteAddLabel = $t('actions.addToFavorites');
$favoriteRemoveLabel = $t('actions.removeFromFavorites');
?>
<section class="food-detail" data-food-card data-food-id="<?= $foodId ?>" data-favorited="<?= $isFavorited ? 'true' : 'false' ?>">
    <a class="food-detail__back" href="/explore">&larr; <?= $this->escape($t('foodDetail.backToCatalog')) ?></a>

    <article class="food-detail__panel">
        <div>
            <img class="food-detail__image" src="<?= $this->escape($food['image_url'] ?? '') ?>" alt="<?= $this->escape($food['name'] ?? '') ?>">
        </div>

        <div class="food-detail__content">
            <span class="food-detail__badge">
                <?= $this->escape(trim(($food['category_icon'] ?? '') . ' ' . ($food['category_name'] ?? ''))) ?>
            </span>

            <div class="food-detail__title-row">
                <h1 class="food-detail__title"><?= $this->escape($food['name'] ?? '') ?></h1>
                <button
                    type="button"
                    class="btn btn--outline btn--sm"
                    data-favorite-toggle
                    data-food-id="<?= $foodId ?>"
                    data-favorited="<?= $isFavorited ? 'true' : 'false' ?>"
                    data-favorite-label-add="<?= $this->escape($favoriteAddLabel) ?>"
                    data-favorite-label-remove="<?= $this->escape($favoriteRemoveLabel) ?>"
                ><?= $this->escape($isFavorited ? $favoriteRemoveLabel : $favoriteAddLabel) ?></button>
            </div>

            <p class="food-detail__description"><?= $this->escape($food['description'] ?? '') ?></p>

            <div class="food-detail__meta">
                <div class="food-detail__meta-card">
                    <span class="food-detail__meta-label"><?= $this->escape($t('foodDetail.area')) ?></span>
                    <span class="food-detail__meta-value"><?= $this->escape($food['area'] ?? '') ?></span>
                </div>

                <div class="food-detail__meta-card">
                    <span class="food-detail__meta-label"><?= $this->escape($t('foodDetail.priceRange')) ?></span>
                    <span class="food-detail__meta-value"><?= $this->escape($food['price_range'] ?? '') ?></span>
                </div>

                <div class="food-detail__meta-card">
                    <span class="food-detail__meta-label"><?= $this->escape($t('foodDetail.rating')) ?></span>
                    <span class="food-detail__meta-value"><?= $this->escape($stars) ?> · <?= $this->escape(number_format($rating, 1)) ?></span>
                </div>

                <div class="food-detail__meta-card food-detail__meta-card--coordinates">
                    <span class="food-detail__meta-label"><?= $this->escape($t('foodDetail.coordinates')) ?></span>
                    <span class="food-detail__meta-value">
                        <?= $this->escape(number_format((float) ($food['latitude'] ?? 0), 4)) ?>,
                        <?= $this->escape(number_format((float) ($food['longitude'] ?? 0), 4)) ?>
                    </span>
                </div>
            </div>
        </div>
    </article>

    <section class="food-detail__map-panel">
        <h2 class="food-detail__map-title"><?= $this->escape($t('foodDetail.location')) ?></h2>
        <div id="food-detail-map" aria-label="<?= $this->escape($t('foodDetail.mapAria', ['name' => (string) ($food['name'] ?? '')])) ?>"></div>
    </section>
</section>

<script>
    window.__FOOD_DETAIL__ = <?= json_encode($food, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
