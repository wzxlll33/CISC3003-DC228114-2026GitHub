<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';

$stats = is_array($stats ?? null) ? $stats : [];
$recentSearches = is_array($recentSearches ?? null) ? $recentSearches : [];
$recentBrowses = is_array($recentBrowses ?? null) ? $recentBrowses : [];

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

$memberSince = static function (?string $value) use ($locale, $t): string {
    if (!is_string($value) || trim($value) === '') {
        return $t('common.recentlyJoined');
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return ($locale ?? 'zh') === 'zh' ? date('Y年n月', $timestamp) : date('M Y', $timestamp);
};

$username = (string) ($user['username'] ?? 'Explorer');
?>
<div class="dashboard dashboard--street-sign">
    <aside class="dashboard__sidebar">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-sidebar.php'; ?>
    </aside>

    <main class="dashboard__content">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-content-nav.php'; ?>
        <section class="dashboard-overview dashboard-surface">
            <?php include ROOT_PATH . '/app/Views/partials/flash-message.php'; ?>

            <header class="dashboard-hero">
                <div>
                    <span class="dashboard-eyebrow"><?= $this->escape($t('dashboard.overviewEyebrow')) ?></span>
                    <h1 class="dashboard-title"><?= $this->escape($t('dashboard.overviewTitle', ['name' => $username])) ?></h1>
                    <p class="dashboard-lead"><?= $this->escape($t('dashboard.overviewLead')) ?></p>
                </div>
                <div class="dashboard-quick-actions" aria-label="<?= $this->escape($t('dashboard.quickActions')) ?>">
                    <a class="btn btn--primary btn--sm" href="/explore"><?= $this->escape($t('dashboard.openExplore')) ?></a>
                    <a class="btn btn--outline btn--sm" href="/dashboard/profile"><?= $this->escape($t('dashboard.editProfile')) ?></a>
                </div>
            </header>

            <section class="dashboard-overview__stats" aria-label="<?= $this->escape($t('dashboard.overview')) ?>">
                <article class="stat-card">
                    <span class="stat-card__label"><?= $this->escape($t('dashboard.totalSearches')) ?></span>
                    <p class="stat-card__value"><?= $this->escape((string) ($stats['total_searches'] ?? 0)) ?></p>
                    <p class="stat-card__note"><?= $this->escape($t('dashboard.totalSearchesNote')) ?></p>
                </article>

                <article class="stat-card">
                    <span class="stat-card__label"><?= $this->escape($t('dashboard.totalBrowses')) ?></span>
                    <p class="stat-card__value"><?= $this->escape((string) ($stats['total_browses'] ?? 0)) ?></p>
                    <p class="stat-card__note"><?= $this->escape($t('dashboard.totalBrowsesNote')) ?></p>
                </article>

                <article class="stat-card">
                    <span class="stat-card__label"><?= $this->escape($t('dashboard.totalFavorites')) ?></span>
                    <p class="stat-card__value"><?= $this->escape((string) ($stats['total_favorites'] ?? 0)) ?></p>
                    <p class="stat-card__note"><?= $this->escape($t('dashboard.totalFavoritesNote')) ?></p>
                </article>

                <article class="stat-card">
                    <span class="stat-card__label"><?= $this->escape($t('dashboard.memberSince')) ?></span>
                    <p class="stat-card__value stat-card__value--date"><?= $this->escape($memberSince($stats['member_since'] ?? '')) ?></p>
                    <p class="stat-card__note"><?= $this->escape($t('dashboard.memberSinceNote')) ?></p>
                </article>
            </section>

            <section class="dashboard-overview__grid">
                <article class="dashboard-panel">
                    <div class="dashboard-panel__header">
                        <h2 class="dashboard-panel__title"><?= $this->escape($t('dashboard.recentSearches')) ?></h2>
                        <a class="dashboard-panel__link" href="/dashboard/search-history"><?= $this->escape($t('common.viewAll')) ?></a>
                    </div>

                    <?php if ($recentSearches === []): ?>
                        <p class="dashboard-empty"><?= $this->escape($t('dashboard.emptySearches')) ?></p>
                    <?php else: ?>
                        <ul class="dashboard-list">
                            <?php foreach ($recentSearches as $entry): ?>
                                <li class="dashboard-list__item">
                                    <a class="dashboard-list__title" href="<?= $this->escape($entry['rerun_url'] ?? '/explore') ?>">
                                        <?= $this->escape($entry['query'] ?? '') ?>
                                    </a>
                                    <div class="dashboard-list__meta">
                                        <span><?= $this->escape($formatDate($entry['created_at'] ?? '')) ?></span>
                                        <span><?= $this->escape($t('dashboard.resultsCount', ['count' => (int) ($entry['results_count'] ?? 0)])) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>

                <article class="dashboard-panel">
                    <div class="dashboard-panel__header">
                        <h2 class="dashboard-panel__title"><?= $this->escape($t('dashboard.recentBrowses')) ?></h2>
                        <a class="dashboard-panel__link" href="/dashboard/browse-history"><?= $this->escape($t('common.viewAll')) ?></a>
                    </div>

                    <?php if ($recentBrowses === []): ?>
                        <p class="dashboard-empty"><?= $this->escape($t('dashboard.emptyBrowses')) ?></p>
                    <?php else: ?>
                        <ul class="dashboard-list">
                            <?php foreach ($recentBrowses as $entry): ?>
                                <?php
                                $type = (string) ($entry['type'] ?? 'food');
                                $item = is_array($entry['item'] ?? null)
                                    ? $entry['item']
                                    : (is_array($entry['food'] ?? null) ? $entry['food'] : []);
                                $itemId = (int) ($item['id'] ?? 0);
                                $href = $type === 'restaurant' ? '/restaurant/' . $itemId : '/food/' . $itemId;
                                ?>
                                <li class="dashboard-list__item dashboard-list__item--media">
                                    <a class="dashboard-media" href="<?= $this->escape($href) ?>">
                                        <img class="dashboard-media__image" src="<?= $this->escape($item['image_url'] ?? '') ?>" alt="<?= $this->escape($item['name'] ?? '') ?>">
                                        <span class="dashboard-media__body">
                                            <strong><?= $this->escape($item['name'] ?? '') ?></strong>
                                            <span><?= $this->escape($item['category_name'] ?? '') ?></span>
                                            <small><?= $this->escape($formatDate($entry['created_at'] ?? '')) ?></small>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            </section>
        </section>
    </main>
</div>
