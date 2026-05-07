<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';
$restaurants = is_array($restaurants ?? null) ? $restaurants : [];
$csrfService = isset($app) && is_object($app) && method_exists($app, 'csrf') ? $app->csrf() : null;
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
                    <h1 class="dashboard-title">&#24215;&#38138;&#31649;&#29702;</h1>
                    <p class="dashboard-lead">&#26032;&#22686;&#12289;&#20462;&#25913;&#21644;&#21024;&#38500;&#39184;&#21381;&#36164;&#26009;&#65292;&#24182;&#20174;&#27599;&#20010;&#24215;&#38138;&#20869;&#31649;&#29702;&#33756;&#24335;&#12290;</p>
                </div>
                <a class="btn btn--primary btn--sm" href="/admin/restaurants/create">&#26032;&#22686;&#24215;&#38138;</a>
            </header>

            <div class="dashboard-table-card admin-table-card">
                <table class="dashboard-table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>&#24215;&#38138;</th>
                            <th>&#21306;&#22495;</th>
                            <th>&#35780;&#20998;</th>
                            <th>&#30005;&#35805;</th>
                            <th>&#25805;&#20316;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <?php $restaurantId = (int) ($restaurant['id'] ?? 0); ?>
                            <tr>
                                <td data-label="ID"><?= $restaurantId ?></td>
                                <td data-label="&#24215;&#38138;">
                                    <a class="dashboard-table__strong-link" href="/restaurant/<?= $restaurantId ?>">
                                        <?= $this->escape($restaurant['name_zh'] ?? $restaurant['name_en'] ?? '') ?>
                                    </a>
                                    <div class="admin-muted"><?= $this->escape($restaurant['address_zh'] ?? $restaurant['address_en'] ?? '') ?></div>
                                </td>
                                <td data-label="&#21306;&#22495;"><?= $this->escape($restaurant['area_zh'] ?? $restaurant['area_en'] ?? '') ?></td>
                                <td data-label="&#35780;&#20998;"><?= $this->escape(number_format((float) ($restaurant['avg_rating'] ?? 0), 1)) ?> · <?= (int) ($restaurant['review_count'] ?? 0) ?></td>
                                <td data-label="&#30005;&#35805;"><?= $this->escape($restaurant['phone'] ?? '') ?></td>
                                <td data-label="&#25805;&#20316;">
                                    <div class="admin-actions">
                                        <a class="btn btn--outline btn--sm" href="/admin/restaurants/<?= $restaurantId ?>/edit">&#32534;&#36753;</a>
                                        <a class="btn btn--outline btn--sm" href="/admin/restaurants/<?= $restaurantId ?>/edit#restaurant-foods">&#33756;&#24335;</a>
                                        <form method="post" action="/admin/restaurants/<?= $restaurantId ?>/delete" onsubmit="return confirm('Delete this restaurant?');">
                                            <?php if ($csrfService): ?>
                                                <?= $csrfService->tokenField() ?>
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn--outline btn--sm">&#21024;&#38500;</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
