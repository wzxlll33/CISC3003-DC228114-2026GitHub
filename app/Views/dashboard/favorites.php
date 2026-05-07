<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';

$favorites = is_array($favorites ?? null) ? $favorites : [];

$formatDate = static function (?string $value) use ($locale): string {
    if (!is_string($value) || trim($value) === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return ($locale ?? 'zh') === 'zh' ? date('Y年n月j日 H:i', $timestamp) : date('M j, Y · g:i A', $timestamp);
};
?>
<div class="dashboard dashboard--street-sign">
    <aside class="dashboard__sidebar">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-sidebar.php'; ?>
    </aside>

    <main class="dashboard__content">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-content-nav.php'; ?>
        <section class="dashboard-favorites dashboard-surface">
            <?php include ROOT_PATH . '/app/Views/partials/flash-message.php'; ?>

            <header class="dashboard-hero">
                <div>
                    <span class="dashboard-eyebrow"><?= $this->escape($t('dashboard.favorites')) ?></span>
                    <h1 class="dashboard-title"><?= $this->escape($t('dashboard.favoritesTitle')) ?></h1>
                    <p class="dashboard-lead"><?= $this->escape($t('dashboard.favoritesLead')) ?></p>
                </div>
                <a class="btn btn--primary btn--sm" href="/explore"><?= $this->escape($t('dashboard.openExplore')) ?></a>
            </header>

            <?php if ($favorites === []): ?>
                <div class="dashboard-empty dashboard-empty--large">
                    <div class="dashboard-empty__icon" aria-hidden="true">♡</div>
                    <h2><?= $this->escape($t('dashboard.favoritesEmptyTitle')) ?></h2>
                    <p><?= $this->escape($t('dashboard.favoritesEmptyLead')) ?></p>
                </div>
            <?php else: ?>
                <div class="dashboard-favorites__grid" data-dashboard-favorites-grid>
                    <?php foreach ($favorites as $food): ?>
                        <article class="dashboard-favorites__card" data-dashboard-favorite-card data-food-id="<?= (int) ($food['id'] ?? 0) ?>">
                            <a class="dashboard-favorites__image-link" href="/food/<?= (int) ($food['id'] ?? 0) ?>">
                                <img class="dashboard-favorites__image" src="<?= $this->escape($food['image_url'] ?? '') ?>" alt="<?= $this->escape($food['name'] ?? '') ?>">
                            </a>

                            <div class="dashboard-favorites__body">
                                <div>
                                    <a class="dashboard-favorites__title-link" href="/food/<?= (int) ($food['id'] ?? 0) ?>">
                                        <h2 class="dashboard-favorites__food-title"><?= $this->escape($food['name'] ?? '') ?></h2>
                                    </a>
                                    <p class="dashboard-favorites__category"><?= $this->escape(trim(($food['category_icon'] ?? '') . ' ' . ($food['category_name'] ?? ''))) ?></p>
                                </div>

                                <div class="dashboard-favorites__meta">
                                    <span>⌖ <?= $this->escape($food['area'] ?? '') ?></span>
                                    <span><?= $this->escape($food['price_range'] ?? '') ?></span>
                                    <span>★ <?= $this->escape(number_format((float) ($food['rating'] ?? 0), 1)) ?></span>
                                </div>

                                <div class="dashboard-favorites__actions">
                                    <span class="dashboard-favorites__date"><?= $this->escape($formatDate($food['favorited_at'] ?? '')) ?></span>
                                    <button
                                        type="button"
                                        class="btn btn--outline btn--sm"
                                        data-favorite-toggle
                                        data-food-id="<?= (int) ($food['id'] ?? 0) ?>"
                                        data-favorited="true"
                                    >
                                        <?= $this->escape($t('dashboard.removeFavorite')) ?>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
