<?php
$username = trim((string) ($user['username'] ?? 'Guest'));
$initials = '';

foreach (preg_split('/\s+/', $username) ?: [] as $part) {
    if ($part === '') {
        continue;
    }

    $initials .= strtoupper(function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1));

    if (strlen($initials) >= 2) {
        break;
    }
}

if ($initials === '') {
    $initials = 'TM';
}

$csrfService = isset($app) && is_object($app) && method_exists($app, 'csrf') ? $app->csrf() : null;
$isAdmin = strtolower((string) ($user['email'] ?? '')) === 'demo@example.com';

$navItems = [
    ['icon' => 'O', 'label' => $t('dashboard.overview'), 'href' => '/dashboard', 'key' => 'overview'],
    ['icon' => 'P', 'label' => $t('dashboard.profile'), 'href' => '/dashboard/profile', 'key' => 'profile'],
    ['icon' => 'S', 'label' => $t('dashboard.searchHistory'), 'href' => '/dashboard/search-history', 'key' => 'search-history'],
    ['icon' => 'B', 'label' => $t('dashboard.browseHistory'), 'href' => '/dashboard/browse-history', 'key' => 'browse-history'],
    ['icon' => 'H', 'label' => $t('dashboard.favorites'), 'href' => '/dashboard/favorites', 'key' => 'favorites'],
];

if ($isAdmin) {
    $navItems[] = ['icon' => 'R', 'label' => '店铺管理', 'href' => '/admin/restaurants', 'key' => 'admin-restaurants'];
    $navItems[] = ['icon' => '!', 'label' => '反馈处理', 'href' => '/admin/feedback', 'key' => 'admin-feedback'];
}
?>

<div class="dashboard-sidebar">
    <div class="dashboard-sidebar__profile">
        <div class="dashboard-sidebar__avatar"><?= $this->escape($initials) ?></div>
        <div class="dashboard-sidebar__identity">
            <p class="dashboard-sidebar__name"><?= $this->escape($username) ?></p>
            <p class="dashboard-sidebar__subtext"><?= $this->escape($t('dashboard.member')) ?></p>
        </div>
        <div class="dashboard-sidebar__footer">
            <form class="dashboard-sidebar__logout-form" method="post" action="/logout">
                <?php if ($csrfService): ?>
                    <?= $csrfService->tokenField() ?>
                <?php endif; ?>
                <button type="submit" class="dashboard-sidebar__logout" data-i18n="nav.logout"><?= $this->escape($t('nav.logout')) ?></button>
            </form>
            <a class="dashboard-sidebar__back" href="/"><span aria-hidden="true">&larr;</span> <span><?= $this->escape($t('nav.backHome')) ?></span></a>
        </div>
    </div>

    <nav class="dashboard-sidebar__nav" aria-label="<?= $this->escape($t('nav.dashboard')) ?>">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = ($activePage ?? '') === $item['key']; ?>
            <a
                class="dashboard-sidebar__link<?= $isActive ? ' dashboard-sidebar__link--active' : '' ?>"
                href="<?= $this->escape($item['href']) ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >
                <span class="dashboard-sidebar__icon" aria-hidden="true"><?= $this->escape($item['icon']) ?></span>
                <span><?= $this->escape($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
