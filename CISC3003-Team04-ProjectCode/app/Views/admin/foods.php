<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';
$foods = is_array($foods ?? null) ? $foods : [];
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
                    <h1 class="dashboard-title">菜式管理</h1>
                    <p class="dashboard-lead">维护菜式名称、分类、图片、地图坐标，以及它出现在哪些店铺。</p>
                </div>
                <a class="btn btn--primary btn--sm" href="/admin/foods/create">新增菜式</a>
            </header>

            <div class="dashboard-table-card admin-table-card">
                <table class="dashboard-table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>菜式</th>
                            <th>分类</th>
                            <th>区域</th>
                            <th>评分</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($foods as $food): ?>
                            <?php $foodId = (int) ($food['id'] ?? 0); ?>
                            <tr>
                                <td data-label="ID"><?= $foodId ?></td>
                                <td data-label="菜式">
                                    <a class="dashboard-table__strong-link" href="/food/<?= $foodId ?>">
                                        <?= $this->escape($food['name_zh'] ?? $food['name_en'] ?? '') ?>
                                    </a>
                                    <div class="admin-muted"><?= $this->escape($food['price_range'] ?? '') ?></div>
                                </td>
                                <td data-label="分类"><?= $this->escape(trim(($food['category_icon'] ?? '') . ' ' . ($food['category_name_zh'] ?? $food['category_name_en'] ?? ''))) ?></td>
                                <td data-label="区域"><?= $this->escape($food['area_zh'] ?? $food['area_en'] ?? '') ?></td>
                                <td data-label="评分"><?= $this->escape(number_format((float) ($food['rating'] ?? 0), 1)) ?></td>
                                <td data-label="操作">
                                    <div class="admin-actions">
                                        <a class="btn btn--outline btn--sm" href="/admin/foods/<?= $foodId ?>/edit">编辑</a>
                                        <form method="post" action="/admin/foods/<?= $foodId ?>/delete" onsubmit="return confirm('确定删除这道菜式？');">
                                            <?php if ($csrfService): ?>
                                                <?= $csrfService->tokenField() ?>
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn--outline btn--sm">删除</button>
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
