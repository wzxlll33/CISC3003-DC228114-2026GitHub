<?php
$layout = 'layouts/main';

$leafletCss = is_file(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.css') ? file_get_contents(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.css') : '';
$extraHead = '<style>' . "\n" . $leafletCss . "\n" . '</style>';

$leafletScript = is_file(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.js') ? file_get_contents(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.js') : '';
$mapScript = is_file(ROOT_PATH . '/public/assets/js/map.js') ? file_get_contents(ROOT_PATH . '/public/assets/js/map.js') : '';
$extraScripts = '<script>' . "\n" . $leafletScript . "\n" . '</script>' . "\n"
    . '<script>' . "\n" . $mapScript . "\n" . '</script>';

$restaurant = is_array($restaurant ?? null) ? $restaurant : [];
$foods = is_array($restaurant['foods'] ?? null) ? $restaurant['foods'] : [];
$reviews = is_array($restaurant['reviews'] ?? null) ? $restaurant['reviews'] : [];
$stats = is_array($restaurant['rating_stats'] ?? null) ? $restaurant['rating_stats'] : [];
$foodCount = count($foods);
$avgRating = (float) ($restaurant['avg_rating'] ?? 0);
$googleRating = isset($restaurant['google_rating']) ? (float) $restaurant['google_rating'] : null;
$amapRating = isset($restaurant['amap_rating']) ? (float) $restaurant['amap_rating'] : null;
$reviewCount = (int) ($restaurant['review_count'] ?? count($reviews));
$currentUserId = (int) ($currentUserId ?? 0);
$isLoggedIn = !empty($isLoggedIn);
$hasReviewed = !empty($hasReviewed);
$canWriteReview = !empty($canWriteReview);
$notAvailable = 'N/A';
$favoriteAddLabel = $t('actions.addToFavorites');
$favoriteRemoveLabel = $t('actions.removeFromFavorites');
$feedbackLocale = in_array($locale ?? null, ['zh', 'en', 'pt'], true) ? (string) $locale : 'zh';
$feedbackOpenLabel = [
    'zh' => '回報問題',
    'en' => 'Report issue',
    'pt' => 'Reportar problema',
][$feedbackLocale];

$primaryTagName = '';

foreach (($restaurant['tags'] ?? []) as $tag) {
    $tagName = trim((string) ($tag['name'] ?? ''));

    if ($tagName !== '') {
        $primaryTagName = $tagName;
        break;
    }
}

$restaurantTypeLabel = $primaryTagName !== '' ? $primaryTagName : $t('restaurantDetail.restaurant');
$restaurantAddress = trim((string) ($restaurant['address'] ?? '')) !== '' ? (string) ($restaurant['address'] ?? '') : $notAvailable;
$restaurantPhone = trim((string) ($restaurant['phone'] ?? '')) !== '' ? (string) ($restaurant['phone'] ?? '') : $notAvailable;
$restaurantOpeningHours = trim((string) ($restaurant['opening_hours'] ?? '')) !== '' ? (string) ($restaurant['opening_hours'] ?? '') : $notAvailable;

$renderStars = static function (float $rating): string {
    $filled = max(0, min(5, (int) round($rating)));
    return str_repeat("\u{2605}", $filled) . str_repeat("\u{2606}", 5 - $filled);
};

$formatRating = static function (?float $rating): string {
    if ($rating === null) {
        return 'N/A';
    }

    return rtrim(rtrim(number_format($rating, 2), '0'), '.');
};
?>

<section class="restaurant-detail" data-review-root data-restaurant-id="<?= (int) ($restaurant['id'] ?? 0) ?>">
    <nav class="restaurant-detail__toolbar" aria-label="<?= $this->escape($t('restaurantDetail.breadcrumb')) ?>">
        <a class="restaurant-detail__back-link" href="/explore">
            <span aria-hidden="true">&larr;</span>
            <span><?= $this->escape($t('restaurantDetail.backToExplore')) ?></span>
        </a>
    </nav>

    <article class="restaurant-detail__hero">
        <img class="restaurant-detail__hero-image" src="<?= $this->escape($restaurant['image_url'] ?? '') ?>" alt="<?= $this->escape($restaurant['name'] ?? '') ?>">

        <div class="restaurant-detail__hero-meta">
            <div class="restaurant-detail__hero-top">
                <div>
                    <p class="restaurant-detail__eyebrow"><?= $this->escape($t('restaurantDetail.breadcrumb')) ?></p>
                    <h1 style="margin: 0;"><?= $this->escape($restaurant['name'] ?? '') ?></h1>
                </div>
                <div class="restaurant-detail__hero-actions">
                    <button
                        type="button"
                        class="btn btn--outline"
                        data-feedback-open
                        data-feedback-context-type="restaurant"
                        data-feedback-restaurant-id="<?= (int) ($restaurant['id'] ?? 0) ?>"
                        data-feedback-context-label="<?= $this->escape($restaurant['name'] ?? '') ?>"
                    ><?= $this->escape($feedbackOpenLabel) ?></button>
                </div>
            </div>

            <p><?= $this->escape($restaurant['description'] ?? '') ?></p>

            <div class="restaurant-detail__mobile-summary" aria-label="<?= $this->escape($t('restaurantDetail.info')) ?>">
                <span class="restaurant-detail__mobile-rating"><span class="restaurant-detail__mobile-rating-label"><?= $this->escape($t('restaurantDetail.overallRating')) ?></span><strong data-average-rating><?= $this->escape($formatRating($avgRating)) ?></strong><span aria-hidden="true">★</span></span>
                <span><?= $this->escape($restaurantTypeLabel) ?></span>
                <span><?= $this->escape($restaurantOpeningHours) ?></span>
            </div>

            <div class="restaurant-detail__mobile-contact">
                <button
                    type="button"
                    class="restaurant-detail__mobile-address"
                    data-restaurant-mobile-map-open
                    aria-controls="restaurant-map"
                ><strong><?= $this->escape($t('restaurantDetail.address')) ?>:</strong><span><?= $this->escape($restaurantAddress) ?></span></button>
                <span class="restaurant-detail__mobile-phone"><strong><?= $this->escape($t('restaurantDetail.phone')) ?>:</strong><span><?= $this->escape($restaurantPhone) ?></span></span>
            </div>

            <div class="restaurant-card__meta restaurant-detail__stats">
                <span class="stat-card"><span class="stat-card__value" data-average-rating><?= $this->escape($formatRating($avgRating)) ?></span><span class="stat-card__label"><?= $this->escape($t('restaurantDetail.overallRating')) ?></span></span>
                <span class="stat-card"><span class="stat-card__value"><?= $this->escape($formatRating($googleRating)) ?></span><span class="stat-card__label"><?= $this->escape($t('restaurantDetail.googleRating')) ?></span></span>
                <span class="stat-card"><span class="stat-card__value"><?= $this->escape($formatRating($amapRating)) ?></span><span class="stat-card__label"><?= $this->escape($t('restaurantDetail.amapRating')) ?></span></span>
                <span class="stat-card"><span class="stat-card__value" data-review-count><?= $reviewCount ?></span><span class="stat-card__label"><?= $this->escape($t('restaurantDetail.reviews')) ?></span></span>
                <span class="stat-card"><span class="stat-card__value"><?= $this->escape($restaurant['price_range'] ?? $notAvailable) ?></span><span class="stat-card__label"><?= $this->escape($t('restaurantDetail.price')) ?></span></span>
            </div>

            <div class="restaurant-detail__meta-row">
            <div class="restaurant-card__rating"><?= $this->escape($renderStars($avgRating)) ?> · <?= $this->escape($formatRating($avgRating)) ?></div>

            <div class="restaurant-detail__tags">
                <?php foreach (($restaurant['tags'] ?? []) as $tag): ?>
                    <span class="tag-pill tag-pill--<?= $this->escape($tag['slug'] ?? 'default') ?>"><?= $this->escape($tag['name'] ?? '') ?></span>
                <?php endforeach; ?>
            </div>
            </div>
        </div>
    </article>

    <nav class="restaurant-detail__section-nav" aria-label="<?= $this->escape($t('restaurantDetail.breadcrumb')) ?>">
        <a href="#restaurant-info"><?= $this->escape($t('restaurantDetail.info')) ?></a>
        <a href="#restaurant-map"><?= $this->escape($t('restaurantDetail.map')) ?></a>
        <a href="#restaurant-dishes"><?= $this->escape($t('restaurantDetail.signatureDishes')) ?></a>
        <a href="#restaurant-reviews"><?= $this->escape($t('restaurantDetail.reviews')) ?></a>
    </nav>

    <div class="restaurant-detail__compact-grid">
    <section id="restaurant-info" class="restaurant-detail__info">
        <h2 style="margin-top: 0;"><?= $this->escape($t('restaurantDetail.info')) ?></h2>
        <div class="restaurant-detail__info-grid">
            <div class="restaurant-detail__info-card"><strong><?= $this->escape($t('restaurantDetail.address')) ?></strong><span><?= $this->escape($restaurant['address'] ?? $notAvailable) ?></span></div>
            <div class="restaurant-detail__info-card"><strong><?= $this->escape($t('restaurantDetail.phone')) ?></strong><span><?= $this->escape($restaurant['phone'] ?? $notAvailable) ?></span></div>
            <div class="restaurant-detail__info-card"><strong><?= $this->escape($t('restaurantDetail.openingHours')) ?></strong><span><?= $this->escape($restaurant['opening_hours'] ?? $notAvailable) ?></span></div>
            <div class="restaurant-detail__info-card"><strong><?= $this->escape($t('restaurantDetail.area')) ?></strong><span><?= $this->escape($restaurant['area'] ?? $notAvailable) ?></span></div>
        </div>
    </section>

    <section id="restaurant-map" class="restaurant-detail__map-panel" data-restaurant-mobile-map-panel aria-hidden="false">
        <button type="button" class="restaurant-detail__map-backdrop" data-restaurant-mobile-map-close aria-label="<?= $this->escape($t('restaurantDetail.map')) ?>"></button>
        <div class="restaurant-detail__map-dialog" role="dialog" aria-modal="true" aria-labelledby="restaurant-map-title">
            <div class="restaurant-detail__map-modal-head">
                <div>
                    <h2 id="restaurant-map-title" style="margin-top: 0;"><?= $this->escape($t('restaurantDetail.map')) ?></h2>
                    <p><?= $this->escape($restaurantAddress) ?></p>
                </div>
                <button type="button" class="restaurant-detail__map-close" data-restaurant-mobile-map-close aria-label="<?= $this->escape($t('restaurantDetail.map')) ?>">&times;</button>
            </div>
            <div id="restaurant-detail-map" aria-label="<?= $this->escape($t('foodDetail.mapAria', ['name' => (string) ($restaurant['name'] ?? '')])) ?>"></div>
        </div>
    </section>
    </div>

    <section id="restaurant-dishes" class="restaurant-detail__dishes">
        <div class="restaurant-detail__review-actions">
            <h2 style="margin: 0;"><?= $this->escape($t('restaurantDetail.signatureDishes')) ?></h2>
            <div class="restaurant-detail__section-actions">
                <span><?= $this->escape($t('restaurantDetail.dishesCount', ['count' => $foodCount])) ?></span>
                <?php if ($foodCount > 2): ?>
                    <button type="button" class="btn btn--outline btn--sm restaurant-detail__more-btn" data-detail-modal-open="dishes" data-detail-more-button="dishes" data-detail-more-count="<?= $foodCount ?>" data-detail-preview-limit="2"><?= $this->escape($t('restaurantDetail.viewMore')) ?></button>
                <?php endif; ?>
            </div>
        </div>

        <div class="restaurant-detail__dishes-grid">
            <?php foreach ($foods as $food): ?>
                <?php
                $foodId = (int) ($food['id'] ?? 0);
                $isFavorited = !empty($food['is_favorited']);
                ?>
                <article class="food-card" data-food-card data-food-id="<?= $foodId ?>" data-favorited="<?= $isFavorited ? 'true' : 'false' ?>">
                    <img class="food-card__image" src="<?= $this->escape($food['image_url'] ?? '') ?>" alt="<?= $this->escape($food['name'] ?? '') ?>">
                    <div class="food-card__body">
                        <div class="restaurant-card__header">
                            <strong class="food-card__title"><?= $this->escape($food['name'] ?? '') ?></strong>
                            <?php if (!empty($food['is_signature'])): ?>
                                <span class="tag-pill tag-pill--must-try"><?= $this->escape($t('restaurantDetail.signature')) ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?= $this->escape($food['description'] ?? '') ?></p>
                        <div class="restaurant-card__meta">
                            <span><?= $this->escape(trim(($food['category_icon'] ?? '') . ' ' . ($food['category_name'] ?? ''))) ?></span>
                            <span><?= $this->escape($t('restaurantDetail.price')) ?> <?= $this->escape($food['price_range'] ?? $notAvailable) ?></span>
                        <span><?= $this->escape($t('restaurantDetail.rating')) ?> <?= $this->escape($formatRating((float) ($food['rating'] ?? 0))) ?></span>
                        </div>
                        <div class="food-card__inline-actions">
                            <a class="btn btn--outline btn--sm" href="/food/<?= $foodId ?>"><?= $this->escape($t('actions.viewDetails')) ?></a>
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
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="restaurant-reviews" class="restaurant-detail__reviews">
        <div class="restaurant-detail__review-actions">
            <div class="restaurant-detail__review-title-group">
                <h2 style="margin: 0;"><?= $this->escape($t('restaurantDetail.reviews')) ?></h2>
                    <span class="restaurant-detail__review-score"><strong data-average-rating><?= $this->escape($formatRating($avgRating)) ?></strong><span aria-hidden="true">★</span><span data-review-count><?= $this->escape($t('restaurantDetail.reviewsCount', ['count' => $reviewCount])) ?></span></span>
            </div>
            <div class="restaurant-detail__section-actions">
                <?php if (!$isLoggedIn): ?>
                    <a class="btn btn--outline btn--sm" href="/login"><?= $this->escape($t('restaurantDetail.signInToReview')) ?></a>
                <?php else: ?>
                    <span class="restaurant-detail__review-state" <?= $hasReviewed ? '' : 'hidden' ?> data-review-submitted-state><?= $this->escape($t('restaurantDetail.reviewAlreadySubmitted')) ?></span>
                    <button type="button" class="btn btn--primary" data-review-toggle <?= $canWriteReview ? '' : 'hidden' ?>><?= $this->escape($t('restaurantDetail.writeReview')) ?></button>
                <?php endif; ?>
                <?php if (count($reviews) > 3): ?>
                    <button type="button" class="btn btn--outline btn--sm restaurant-detail__more-btn" data-detail-modal-open="reviews" data-detail-more-button="reviews" data-detail-more-count="<?= count($reviews) ?>" data-detail-preview-limit="3"><?= $this->escape($t('restaurantDetail.viewMore')) ?></button>
                <?php endif; ?>
            </div>
        </div>

        <div class="rating-summary" data-rating-summary>
            <div class="rating-summary__score">
                    <div class="rating-summary__score-value" data-average-rating><?= $this->escape($formatRating($avgRating)) ?></div>
                <div data-average-stars><?= $this->escape($renderStars($avgRating)) ?></div>
                <div data-review-count><?= $this->escape($t('restaurantDetail.reviewsCount', ['count' => $reviewCount])) ?></div>
            </div>

            <div class="rating-summary__bars" data-rating-bars>
                <?php for ($score = 5; $score >= 1; $score--): ?>
                    <?php $count = (int) ($stats[$score] ?? 0); ?>
                    <?php $percentage = $reviewCount > 0 ? ($count / $reviewCount) * 100 : 0; ?>
                    <div class="rating-summary__bar" data-rating-row data-score="<?= $score ?>">
                        <span><?= $score ?><?= $this->escape("\u{2605}") ?></span>
                        <span class="rating-summary__track"><span class="rating-summary__fill" style="width: <?= $this->escape(number_format($percentage, 2, '.', '')) ?>%"></span></span>
                        <span data-rating-count><?= $count ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="flash flash--success" data-review-success hidden></div>
        <div class="flash flash--error" data-review-error hidden></div>

        <form class="review-form" data-review-form hidden>
            <input type="hidden" name="rating" value="0" data-review-rating>
            <div>
                <strong><?= $this->escape($t('restaurantDetail.reviewRating')) ?></strong>
                <div class="review-form__stars" data-star-selector>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="review-form__star" data-star-value="<?= $i ?>" aria-label="<?= $this->escape($t('restaurantDetail.reviewRating') . ' ' . $i) ?>"><?= $this->escape("\u{2605}") ?></button>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <label for="review-food"><strong><?= $this->escape($t('restaurantDetail.dishTried')) ?></strong></label><br>
                <select id="review-food" name="food_id" class="search-bar__input" data-review-food>
                    <option value=""><?= $this->escape($t('restaurantDetail.optional')) ?></option>
                    <?php foreach ($foods as $food): ?>
                        <option value="<?= (int) ($food['id'] ?? 0) ?>"><?= $this->escape($food['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top: 1rem;">
                <label for="review-comment"><strong><?= $this->escape($t('restaurantDetail.comment')) ?></strong></label>
                <textarea id="review-comment" name="comment" class="review-form__comment" data-review-comment placeholder="<?= $this->escape($t('restaurantDetail.commentPlaceholder')) ?>"></textarea>
            </div>

            <div class="review-form__actions" style="margin-top: 1rem;">
                <span><?= $this->escape($t('restaurantDetail.minimumCharacters')) ?></span>
                <button type="submit" class="btn btn--primary review-form__submit"><?= $this->escape($t('restaurantDetail.submitReview')) ?></button>
            </div>
        </form>

        <div class="restaurant-detail__review-list" data-review-list>
            <?php foreach ($reviews as $review): ?>
                <article class="review-card" data-review-id="<?= (int) ($review['id'] ?? 0) ?>">
                    <div class="review-card__header">
                        <div class="review-card__identity">
                            <div class="review-card__user"><strong><?= $this->escape($review['username'] ?? $t('restaurantDetail.guest')) ?></strong></div>
                            <div class="review-card__rating"><?= $this->escape($renderStars((float) ($review['rating'] ?? 0))) ?> · <?= (int) ($review['rating'] ?? 0) ?>/5</div>
                        </div>
                        <div class="review-card__context">
                            <div class="review-card__date"><?= $this->escape(substr((string) ($review['created_at'] ?? ''), 0, 10)) ?></div>
                            <?php if (!empty($review['food_name'])): ?>
                                <div class="review-card__food"><?= $this->escape($review['food_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="review-card__comment"><?= nl2br($this->escape($review['comment'] ?? '')) ?></p>
                    <?php if ((int) ($review['user_id'] ?? 0) === $currentUserId): ?>
                        <div><button type="button" class="btn btn--outline btn--sm" data-review-delete data-review-id="<?= (int) ($review['id'] ?? 0) ?>"><?= $this->escape($t('restaurantDetail.delete')) ?></button></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<div class="detail-modal" data-detail-modal="dishes" hidden>
    <button type="button" class="detail-modal__backdrop" data-detail-modal-close aria-label="<?= $this->escape($t('restaurantDetail.close')) ?>"></button>
    <section class="detail-modal__panel detail-modal__panel--wide" role="dialog" aria-modal="true" aria-labelledby="restaurant-dishes-modal-title">
        <header class="detail-modal__header">
            <div>
                <h2 id="restaurant-dishes-modal-title"><?= $this->escape($t('restaurantDetail.allDishes')) ?></h2>
                <p><?= $this->escape($t('restaurantDetail.dishesCount', ['count' => $foodCount])) ?></p>
            </div>
            <button type="button" class="detail-modal__close" data-detail-modal-close aria-label="<?= $this->escape($t('restaurantDetail.close')) ?>">&times;</button>
        </header>
        <div class="detail-modal__body detail-modal__body--cards">
            <?php foreach ($foods as $food): ?>
                <?php
                $foodId = (int) ($food['id'] ?? 0);
                $isFavorited = !empty($food['is_favorited']);
                ?>
                <article class="food-card" data-food-card data-food-id="<?= $foodId ?>" data-favorited="<?= $isFavorited ? 'true' : 'false' ?>">
                    <img class="food-card__image" src="<?= $this->escape($food['image_url'] ?? '') ?>" alt="<?= $this->escape($food['name'] ?? '') ?>">
                    <div class="food-card__body">
                        <div class="restaurant-card__header">
                            <strong class="food-card__title"><?= $this->escape($food['name'] ?? '') ?></strong>
                            <?php if (!empty($food['is_signature'])): ?>
                                <span class="tag-pill tag-pill--must-try"><?= $this->escape($t('restaurantDetail.signature')) ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?= $this->escape($food['description'] ?? '') ?></p>
                        <div class="restaurant-card__meta">
                            <span><?= $this->escape(trim(($food['category_icon'] ?? '') . ' ' . ($food['category_name'] ?? ''))) ?></span>
                            <span><?= $this->escape($t('restaurantDetail.price')) ?> <?= $this->escape($food['price_range'] ?? $notAvailable) ?></span>
                        <span><?= $this->escape($t('restaurantDetail.rating')) ?> <?= $this->escape($formatRating((float) ($food['rating'] ?? 0))) ?></span>
                        </div>
                        <div class="food-card__inline-actions">
                            <a class="btn btn--outline btn--sm" href="/food/<?= $foodId ?>"><?= $this->escape($t('actions.viewDetails')) ?></a>
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
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="detail-modal" data-detail-modal="reviews" hidden>
    <button type="button" class="detail-modal__backdrop" data-detail-modal-close aria-label="<?= $this->escape($t('restaurantDetail.close')) ?>"></button>
    <section class="detail-modal__panel" role="dialog" aria-modal="true" aria-labelledby="restaurant-reviews-modal-title">
        <header class="detail-modal__header">
            <div>
                <h2 id="restaurant-reviews-modal-title"><?= $this->escape($t('restaurantDetail.allReviews')) ?></h2>
                <p><?= $this->escape($t('restaurantDetail.reviewsCount', ['count' => $reviewCount])) ?></p>
            </div>
            <button type="button" class="detail-modal__close" data-detail-modal-close aria-label="<?= $this->escape($t('restaurantDetail.close')) ?>">&times;</button>
        </header>
        <div class="detail-modal__body detail-modal__body--reviews" data-review-modal-list>
            <?php foreach ($reviews as $review): ?>
                <article class="review-card" data-review-id="<?= (int) ($review['id'] ?? 0) ?>">
                    <div class="review-card__header">
                        <div class="review-card__identity">
                            <div class="review-card__user"><strong><?= $this->escape($review['username'] ?? $t('restaurantDetail.guest')) ?></strong></div>
                            <div class="review-card__rating"><?= $this->escape($renderStars((float) ($review['rating'] ?? 0))) ?> · <?= (int) ($review['rating'] ?? 0) ?>/5</div>
                        </div>
                        <div class="review-card__context">
                            <div class="review-card__date"><?= $this->escape(substr((string) ($review['created_at'] ?? ''), 0, 10)) ?></div>
                            <?php if (!empty($review['food_name'])): ?>
                                <div class="review-card__food"><?= $this->escape($review['food_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="review-card__comment"><?= nl2br($this->escape($review['comment'] ?? '')) ?></p>
                    <?php if ((int) ($review['user_id'] ?? 0) === $currentUserId): ?>
                        <div><button type="button" class="btn btn--outline btn--sm" data-review-delete data-review-id="<?= (int) ($review['id'] ?? 0) ?>"><?= $this->escape($t('restaurantDetail.delete')) ?></button></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
    window.__RESTAURANT_DETAIL__ = {
        restaurant: <?= json_encode($restaurant, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        labels: <?= json_encode([
            'guest' => $t('restaurantDetail.guest'),
            'delete' => $t('restaurantDetail.delete'),
            'reviewsCount' => $t('restaurantDetail.reviewsCount', ['count' => ':count']),
            'validationError' => $t('restaurantDetail.validationError'),
            'submitSuccess' => $t('restaurantDetail.submitSuccess'),
            'submitError' => $t('restaurantDetail.submitError'),
            'deleteSuccess' => $t('restaurantDetail.deleteSuccess'),
            'deleteError' => $t('restaurantDetail.deleteError'),
            'reviewAlreadySubmitted' => $t('restaurantDetail.reviewAlreadySubmitted'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        currentUserId: <?= (int) $currentUserId ?>,
        canWriteReview: <?= $canWriteReview ? 'true' : 'false' ?>
    };
</script>
