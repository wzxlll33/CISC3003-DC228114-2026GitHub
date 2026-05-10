<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';

$entries = is_array($entries ?? null) ? $entries : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];

$currentPage = (int) ($pagination['current_page'] ?? $pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$totalEntries = (int) ($pagination['total'] ?? count($entries));
$baseUrl = (string) ($pagination['base_url'] ?? '/dashboard/search-history');

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
        <section class="dashboard-search-history dashboard-surface">
            <?php include ROOT_PATH . '/app/Views/partials/flash-message.php'; ?>

            <header class="dashboard-hero">
                <div>
                    <span class="dashboard-eyebrow"><?= $this->escape($t('dashboard.searchHistory')) ?></span>
                    <h1 class="dashboard-title"><?= $this->escape($t('dashboard.searchTitle')) ?></h1>
                    <p class="dashboard-lead"><?= $this->escape($t('dashboard.searchLead')) ?></p>
                </div>

                <form method="post" action="/dashboard/clear-search-history" data-confirm="<?= $this->escape($t('dashboard.clearSearchConfirm')) ?>">
                    <?= $app->csrf()->tokenField() ?>
                    <button class="btn btn--primary btn--sm" type="submit"><?= $this->escape($t('common.clearAll')) ?></button>
                </form>
            </header>

            <?php if ($entries === []): ?>
                <div class="dashboard-empty dashboard-empty--large"><?= $this->escape($t('dashboard.emptySearchHistory')) ?></div>
            <?php else: ?>
                <div class="dashboard-table-card">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th scope="col"><?= $this->escape($t('dashboard.query')) ?></th>
                                <th scope="col"><?= $this->escape($t('dashboard.filters')) ?></th>
                                <th scope="col"><?= $this->escape($t('common.resultsColumn')) ?></th>
                                <th scope="col"><?= $this->escape($t('dashboard.date')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <?php $filters = is_array($entry['filters'] ?? null) ? $entry['filters'] : []; ?>
                                <tr>
                                    <td data-label="<?= $this->escape($t('dashboard.query')) ?>">
                                        <a class="dashboard-table__strong-link" href="<?= $this->escape($entry['rerun_url'] ?? ('/explore?q=' . urlencode((string) ($entry['query'] ?? '')))) ?>">
                                            <?= $this->escape($entry['query'] ?? '') ?>
                                        </a>
                                    </td>
                                    <td data-label="<?= $this->escape($t('dashboard.filters')) ?>">
                                        <?php if ($filters === []): ?>
                                            <span class="dashboard-muted"><?= $this->escape($t('common.noFilters')) ?></span>
                                        <?php else: ?>
                                            <div class="dashboard-filter-pills">
                                                <?php foreach ($filters as $filterName => $filterValue): ?>
                                                    <?php if (is_array($filterValue)): ?>
                                                        <?php $filterValue = implode(', ', array_map('strval', $filterValue)); ?>
                                                    <?php endif; ?>
                                                    <span class="dashboard-filter-pill">
                                                        <?= $this->escape(ucwords(str_replace('_', ' ', (string) $filterName))) ?>:
                                                        <?= $this->escape((string) $filterValue) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="<?= $this->escape($t('common.resultsColumn')) ?>"><?= $this->escape((string) ($entry['results_count'] ?? 0)) ?></td>
                                    <td data-label="<?= $this->escape($t('dashboard.date')) ?>"><?= $this->escape($formatDate($entry['created_at'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="dashboard-pagination" aria-label="<?= $this->escape($t('dashboard.searchHistory')) ?>">
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
