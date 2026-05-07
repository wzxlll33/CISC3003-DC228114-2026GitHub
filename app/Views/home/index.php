<?php
$layout = 'layouts/main';

$leafletCss = is_file(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.css') ? file_get_contents(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.css') : '';
$extraHead = '<style>' . "\n" . $leafletCss . "\n" . '</style>';

$leafletScript = is_file(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.js') ? file_get_contents(ROOT_PATH . '/public/assets/vendor/leaflet/leaflet.js') : '';
$mapScript = is_file(ROOT_PATH . '/public/assets/js/map.js') ? file_get_contents(ROOT_PATH . '/public/assets/js/map.js') : '';
$extraScripts = '<script>' . "\n" . $leafletScript . "\n" . '</script>' . "\n"
    . '<script>' . "\n" . $mapScript . "\n" . '</script>';

$restaurants = is_array($restaurants ?? null) ? $restaurants : [];
$foods = is_array($foods ?? null) ? $foods : [];
$topRated = is_array($topRated ?? null) ? $topRated : [];
$categories = is_array($categories ?? null) ? $categories : [];
$favoriteIds = array_map(static fn (mixed $id): int => (int) $id, is_array($favoriteIds ?? null) ? $favoriteIds : []);
$isLoggedIn = isset($app) && is_object($app) && method_exists($app, 'session') ? $app->session()->isLoggedIn() : false;
$csrfService = isset($app) && is_object($app) && method_exists($app, 'csrf') ? $app->csrf() : null;
$resolvedLocale = in_array($locale ?? null, ['zh', 'en', 'pt'], true) ? $locale : 'zh';
$reviewsLabel = $resolvedLocale === 'zh' ? '評論' : ($resolvedLocale === 'pt' ? 'comentários' : 'reviews');
$spotsSuffix = $resolvedLocale === 'zh' ? '間' : ($resolvedLocale === 'pt' ? 'locais' : 'spots');
$feedbackOpenLabel = [
    'zh' => '回報問題',
    'en' => 'Report issue',
    'pt' => 'Reportar problema',
][$resolvedLocale];
?>

<section class="explore-page explore-page--map-first" data-restaurant-catalog>
    <section class="explore-page__map" aria-label="<?= $this->escape($t('explore.mapAria')) ?>">
        <div class="explore-page__map-frame">
            <div id="map-container" aria-label="<?= $this->escape($t('explore.mapContainerAria')) ?>"></div>
        </div>
    </section>

    <section class="explore-search-shell" data-search-shell aria-label="<?= $this->escape($t('explore.shellAria')) ?>">
        <h1 class="sr-only"><?= $this->escape($t('explore.title')) ?></h1>

        <div class="explore-search" role="search">
            <span class="explore-search__icon" aria-hidden="true">⌕</span>
            <label class="sr-only" for="restaurant-search"><?= $this->escape($t('explore.searchLabel')) ?></label>
            <input
                id="restaurant-search"
                class="explore-search__input"
                type="search"
                placeholder="<?= $this->escape($t('explore.searchPlaceholder')) ?>"
                autocomplete="off"
            >
            <button
                type="button"
                class="explore-search__tools"
                data-toggle-tools
                aria-label="<?= $this->escape($t('explore.openTools')) ?>"
                aria-controls="explore-tools-panel"
                aria-expanded="false"
            >☰</button>
        </div>

        <div class="flash flash--error explore-search-error" data-search-error hidden></div>

        <div
            class="explore-search-suggestions"
            data-search-suggestions
            aria-label="<?= $this->escape($t('explore.suggestionsAria')) ?>"
            hidden
        >
            <button type="button" data-search-suggestion="map-location"><?= $this->escape($t('explore.suggestionMapLocation')) ?></button>
            <button type="button" data-search-suggestion="use-location"><?= $this->escape($t('explore.suggestionUseLocation')) ?></button>
            <button type="button" data-search-suggestion="show-all"><?= $this->escape($t('explore.suggestionShowAll')) ?></button>
        </div>

        <div id="explore-tools-panel" class="explore-tools-menu" data-explore-tools hidden>
            <div class="explore-search-shell__title">
                <span><?= $this->escape($t('explore.toolsTitle')) ?></span>
                <strong><?= $this->escape($t('explore.toolsStrong')) ?></strong>
            </div>

            <div class="explore-actions" aria-label="<?= $this->escape($t('explore.toolsAria')) ?>">
                <button type="button" class="explore-action" data-open-results aria-controls="explore-results-panel" aria-expanded="true">
                    <span aria-hidden="true">☰</span>
                    <span><?= $this->escape($t('explore.list')) ?></span>
                    <strong data-results-count><?= count($restaurants) ?></strong>
                </button>
                <button type="button" class="explore-action" data-open-filters aria-haspopup="dialog" aria-controls="explore-filter-panel" aria-expanded="false">
                    <span aria-hidden="true">⛃</span>
                    <span><?= $this->escape($t('explore.filter')) ?></span>
                </button>
                <button type="button" class="explore-action" data-open-must-eat aria-controls="explore-must-eat-drawer" aria-expanded="false">
                    <span aria-hidden="true">🏆</span>
                    <span><?= $this->escape($t('explore.mustEat')) ?></span>
                </button>
                <?php if ($isLoggedIn): ?>
                    <a class="explore-action explore-action--link" href="/dashboard/favorites">
                        <span aria-hidden="true">♥</span>
                        <span><?= $this->escape($t('explore.favorites')) ?></span>
                    </a>
                <?php else: ?>
                    <button type="button" class="explore-action" data-open-local-favorites aria-controls="explore-results-panel" aria-expanded="false">
                        <span aria-hidden="true">♥</span>
                        <span><?= $this->escape($t('explore.favorites')) ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="explore-session-actions" aria-label="<?= $this->escape($t('nav.primary')) ?>">
                    <a class="explore-session-action" href="/dashboard" data-i18n="nav.dashboard"><?= $this->escape($t('nav.dashboard')) ?></a>
                    <form class="explore-session-action-form" method="post" action="/logout">
                        <?php if ($csrfService): ?>
                            <?= $csrfService->tokenField() ?>
                        <?php endif; ?>
                        <button type="submit" class="explore-session-action explore-session-action--logout" data-i18n="nav.logout"><?= $this->escape($t('nav.logout')) ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <p class="explore-active-summary" data-active-filter-summary><?= $this->escape($t('explore.summary', ['count' => count($restaurants)])) ?></p>
        </div>
    </section>

    <aside id="explore-results-panel" class="explore-results-panel" data-results-panel aria-labelledby="explore-results-title">
        <header class="explore-panel-header">
            <div class="explore-panel-header__title">
                <h2 id="explore-results-title"><?= $this->escape($t('explore.resultsTitle')) ?></h2>
                <span class="explore-panel-header__count"><strong data-results-count><?= count($restaurants) ?></strong> <?= $this->escape($spotsSuffix) ?></span>
            </div>
            <div class="explore-panel-header__meta">
                <button type="button" class="explore-panel-close" data-close-results aria-label="<?= $this->escape($t('explore.closeResults')) ?>">×</button>
            </div>
        </header>

        <div class="flash" data-no-results hidden><?= $this->escape($t('explore.empty')) ?></div>
        <div class="explore-page__cards" data-restaurant-cards></div>
    </aside>

    <aside id="explore-must-eat-drawer" class="explore-must-eat-drawer" data-must-eat-panel aria-labelledby="explore-must-eat-title" hidden>
        <header class="explore-panel-header">
            <div>
                <span><?= $this->escape($t('explore.rankingLabel')) ?></span>
                <h2 id="explore-must-eat-title"><?= $this->escape($t('explore.rankingTitle')) ?></h2>
            </div>
            <button type="button" class="explore-panel-close" data-close-must-eat aria-label="<?= $this->escape($t('explore.closeRanking')) ?>">×</button>
        </header>
        <p class="explore-panel-lead"><?= $this->escape($t('explore.rankingLead')) ?></p>
        <div class="must-eat__list" data-top-rated-list></div>
    </aside>

    <div id="explore-filter-panel" class="explore-filter-panel" data-filter-panel role="dialog" aria-modal="false" aria-labelledby="explore-filter-title" hidden>
        <header class="explore-panel-header">
            <div>
                <span><?= $this->escape($t('explore.filterLabel')) ?></span>
                <h2 id="explore-filter-title"><?= $this->escape($t('explore.filterTitle')) ?></h2>
            </div>
            <button type="button" class="explore-panel-close" data-close-filters aria-label="<?= $this->escape($t('explore.closeFilter')) ?>">×</button>
        </header>

        <section class="explore-filter-group" aria-labelledby="filter-service-title">
            <h3 id="filter-service-title"><?= $this->escape($t('explore.serviceTitle')) ?></h3>
            <div class="explore-filter-options">
                <button type="button" class="explore-filter-option is-active" data-filter-service="all"><?= $this->escape($t('explore.serviceAll')) ?></button>
                <button type="button" class="explore-filter-option" data-filter-service="must-eat"><?= $this->escape($t('explore.serviceMustEat')) ?></button>
                <button type="button" class="explore-filter-option" data-filter-service="high-rated"><?= $this->escape($t('explore.serviceHighRated')) ?></button>
                <button type="button" class="explore-filter-option" data-filter-service="nearby"><?= $this->escape($t('explore.serviceNearby')) ?></button>
            </div>
        </section>

        <section class="explore-filter-group" aria-labelledby="filter-category-title">
            <h3 id="filter-category-title"><?= $this->escape($t('explore.categoryTitle')) ?></h3>
            <div class="explore-filter-options explore-filter-options--categories">
                <button type="button" class="explore-filter-option is-active" data-filter-category="all"><?= $this->escape($t('explore.categoryAll')) ?></button>
                <?php foreach ($categories as $category): ?>
                    <button
                        type="button"
                        class="explore-filter-option"
                        data-filter-category="<?= $this->escape($category['slug'] ?? '') ?>"
                    ><?= $this->escape(trim(($category['icon'] ?? '') . ' ' . ($category['name'] ?? ''))) ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <footer class="explore-filter-actions">
            <button type="button" class="btn btn--outline" data-reset-filters><?= $this->escape($t('explore.reset')) ?></button>
            <button type="button" class="btn btn--primary" data-apply-filters><?= $this->escape($t('explore.apply')) ?></button>
        </footer>
    </div>

    <button type="button" class="explore-panel-backdrop" data-panel-backdrop aria-label="<?= $this->escape($t('explore.closeOverlay')) ?>" hidden></button>
</section>

<script>
    window.__RESTAURANTS__ = <?= json_encode($restaurants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__FOODS__ = <?= json_encode($foods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__TOP_RATED__ = <?= json_encode($topRated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__TASTE_OF_MACAU__ = {
        locale: <?= json_encode($resolvedLocale, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        restaurants: <?= json_encode($restaurants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        foods: <?= json_encode($foods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        topRated: <?= json_encode($topRated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        favoriteIds: <?= json_encode($favoriteIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        labels: {
            reviews: <?= json_encode($reviewsLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            viewDetails: <?= json_encode($t('actions.viewDetails'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            openTools: <?= json_encode($t('explore.openTools'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            closeTools: <?= json_encode($t('explore.closeTools'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            allRestaurants: <?= json_encode($t('explore.serviceAll'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            mustEat: <?= json_encode($t('explore.serviceMustEat'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            highRated: <?= json_encode($t('explore.serviceHighRated'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            nearby: <?= json_encode($t('explore.serviceNearby'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            favorites: <?= json_encode($t('explore.favorites'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            nearbyRestaurants: <?= json_encode($t('explore.suggestionNearbyRestaurants'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            nearbyFood: <?= json_encode($t('explore.suggestionNearbyFood'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            mapLocation: <?= json_encode($t('explore.suggestionMapLocation'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            useLocation: <?= json_encode($t('explore.suggestionUseLocation'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            showAll: <?= json_encode($t('explore.suggestionShowAll'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            locationUnavailable: <?= json_encode($t('explore.locationUnavailable'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            locationDenied: <?= json_encode($t('explore.locationDenied'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            locationSearching: <?= json_encode($t('explore.locationSearching'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            showingNearby: <?= json_encode($t('explore.showingNearby'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            locationNotFound: <?= json_encode($t('explore.locationNotFound'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            locationSearchError: <?= json_encode($t('explore.locationSearchError'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            clearMapSelection: <?= json_encode($t('explore.clearMapSelection'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            reportIssue: <?= json_encode($feedbackOpenLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            spotsSuffix: <?= json_encode($spotsSuffix, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        },
        isLoggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>
    };
</script>
