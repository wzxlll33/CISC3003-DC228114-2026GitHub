<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';

$entries = is_array($entries ?? null) ? $entries : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];

$currentPage = (int) ($pagination['current_page'] ?? $pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$totalEntries = (int) ($pagination['total'] ?? count($entries));
$baseUrl = (string) ($pagination['base_url'] ?? '/dashboard/browse-history');

$pageUrl = static function (string $basePath, int $page): string {
    return $basePath . '?page=' . $page;
};

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
        <section class="dashboard-browse-history dashboard-surface">
            <?php include ROOT_PATH . '/app/Views/partials/flash-message.php'; ?>

            <header class="dashboard-hero">
                <div>
                    <span class="dashboard-eyebrow"><?= $this->escape($t('dashboard.browseHistory')) ?></span>
                    <h1 class="dashboard-title"><?= $this->escape($t('dashboard.browseTitle')) ?></h1>
                    <p class="dashboard-lead"><?= $this->escape($t('dashboard.browseLead')) ?></p>
                </div>

                <form method="post" action="/dashboard/clear-browse-history" data-confirm="<?= $this->escape($t('dashboard.clearBrowseConfirm')) ?>">
                    <?= $app->csrf()->tokenField() ?>
                    <button class="btn btn--primary btn--sm" type="submit"><?= $this->escape($t('common.clearAll')) ?></button>
                </form>
            </header>

            <?php if ($entries === []): ?>
                <div class="dashboard-empty dashboard-empty--large"><?= $this->escape($t('dashboard.emptyBrowseHistory')) ?></div>
            <?php else: ?>
                <div class="dashboard-browse-history__grid">
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $type = (string) ($entry['type'] ?? 'food');
                        $item = is_array($entry['item'] ?? null)
                            ? $entry['item']
                            : (is_array($entry['food'] ?? null) ? $entry['food'] : []);
                        $itemId = (int) ($item['id'] ?? 0);
                        $href = $type === 'restaurant' ? '/restaurant/' . $itemId : '/food/' . $itemId;
                        ?>
                        <a class="dashboard-browse-history__card" href="<?= $this->escape($href) ?>">
                            <img
                                class="dashboard-browse-history__image"
                                src="<?= $this->escape($item['image_url'] ?? '') ?>"
                                alt="<?= $this->escape($item['name'] ?? '') ?>"
                            >

                            <span class="dashboard-browse-history__body">
                                <span class="dashboard-browse-history__category"><?= $this->escape($item['category_name'] ?? '') ?></span>
                                <strong class="dashboard-browse-history__name"><?= $this->escape($item['name'] ?? '') ?></strong>
                                <span class="dashboard-browse-history__date"><?= $this->escape($t('dashboard.viewed', ['date' => $formatDate($entry['created_at'] ?? '')])) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="dashboard-pagination" aria-label="<?= $this->escape($t('dashboard.browseHistory')) ?>">
                        <span><?= $this->escape($t('common.pageSummary', ['current' => $currentPage, 'total' => $totalPages, 'count' => $totalEntries])) ?></span>

                        <div class="pagination">
                            <?php if ($currentPage > 1): ?>
                                <a class="pagination__link" href="<?= $this->escape($pageUrl($baseUrl, $currentPage - 1)) ?>"><?= $this->escape($t('common.previous')) ?></a>
                            <?php endif; ?>

                            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                                <?php if ($page === $currentPage): ?>
                                    <span class="pagination__link pagination__link--active"><?= $this->escape((string) $page) ?></span>
                                <?php else: ?>
                                    <a class="pagination__link" href="<?= $this->escape($pageUrl($baseUrl, $page)) ?>"><?= $this->escape((string) $page) ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a class="pagination__link" href="<?= $this->escape($pageUrl($baseUrl, $currentPage + 1)) ?>"><?= $this->escape($t('common.next')) ?></a>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</div>
