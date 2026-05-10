<?php
$dashboardNavItems = [
    ['icon' => 'O', 'label' => $t('dashboard.overview'), 'href' => '/dashboard', 'key' => 'overview'],
    ['icon' => 'P', 'label' => $t('dashboard.profile'), 'href' => '/dashboard/profile', 'key' => 'profile'],
    ['icon' => 'S', 'label' => $t('dashboard.searchHistory'), 'href' => '/dashboard/search-history', 'key' => 'search-history'],
    ['icon' => 'B', 'label' => $t('dashboard.browseHistory'), 'href' => '/dashboard/browse-history', 'key' => 'browse-history'],
    ['icon' => 'H', 'label' => $t('dashboard.favorites'), 'href' => '/dashboard/favorites', 'key' => 'favorites'],
];

if (strtolower((string) ($user['email'] ?? '')) === 'demo@example.com') {
    $dashboardNavItems[] = ['icon' => 'R', 'label' => '店铺管理', 'href' => '/admin/restaurants', 'key' => 'admin-restaurants'];
    $dashboardNavItems[] = ['icon' => '!', 'label' => '反馈处理', 'href' => '/admin/feedback', 'key' => 'admin-feedback'];
}
?>
<nav class="dashboard-sidebar__nav dashboard-content-nav" aria-label="<?= $this->escape($t('nav.dashboard')) ?>">
    <?php foreach ($dashboardNavItems as $item): ?>
        <?php $isActive = ($activePage ?? '') === $item['key']; ?>
        <a
            class="dashboard-content-nav__link<?= $isActive ? ' dashboard-content-nav__link--active' : '' ?>"
            href="<?= $this->escape($item['href']) ?>"
            <?= $isActive ? 'aria-current="page"' : '' ?>
        >
            <span class="dashboard-content-nav__icon" aria-hidden="true"><?= $this->escape($item['icon']) ?></span>
            <span><?= $this->escape($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
