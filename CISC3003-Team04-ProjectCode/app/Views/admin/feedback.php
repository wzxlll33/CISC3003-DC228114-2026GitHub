<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';
$feedbackReports = is_array($feedbackReports ?? null) ? $feedbackReports : [];
$statusCounts = is_array($statusCounts ?? null) ? $statusCounts : [];
$csrfService = isset($app) && is_object($app) && method_exists($app, 'csrf') ? $app->csrf() : null;

$statusLabel = static fn (string $status): string => [
    'new' => '待处理',
    'reviewing' => '处理中',
    'resolved' => '已解决',
][$status] ?? $status;

$issueLabel = static fn (string $issue): string => [
    'missing_store' => '找不到店铺',
    'wrong_address' => '地址或地图错误',
    'closed' => '店铺已关闭',
    'wrong_info' => '资料有误',
    'other' => '其他',
][$issue] ?? $issue;
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
                    <h1 class="dashboard-title">用户反馈</h1>
                    <p class="dashboard-lead">处理用户提交的店铺缺失、地址错误、营业状态和资料更新反馈。</p>
                </div>
                <div class="admin-status-strip" aria-label="Feedback status">
                    <span>待处理 <?= (int) ($statusCounts['new'] ?? 0) ?></span>
                    <span>处理中 <?= (int) ($statusCounts['reviewing'] ?? 0) ?></span>
                    <span>已解决 <?= (int) ($statusCounts['resolved'] ?? 0) ?></span>
                </div>
            </header>

            <?php if ($feedbackReports === []): ?>
                <div class="dashboard-empty dashboard-empty--large">
                    <h2>暂无反馈</h2>
                    <p>用户通过“回报问题”提交的内容会显示在这里。</p>
                </div>
            <?php else: ?>
                <div class="admin-feedback-list">
                    <?php foreach ($feedbackReports as $report): ?>
                        <?php
                        $reportId = (int) ($report['id'] ?? 0);
                        $contextName = (string) ($report['restaurant_name_zh'] ?? $report['restaurant_name_en'] ?? $report['food_name_zh'] ?? $report['food_name_en'] ?? '');
                        ?>
                        <article class="admin-feedback-card">
                            <header class="admin-feedback-card__header">
                                <div>
                                    <span class="admin-feedback-card__type"><?= $this->escape($issueLabel((string) ($report['issue_type'] ?? 'other'))) ?></span>
                                    <h2><?= $contextName !== '' ? $this->escape($contextName) : '一般反馈' ?></h2>
                                </div>
                                <span class="admin-status admin-status--<?= $this->escape($report['status'] ?? 'new') ?>"><?= $this->escape($statusLabel((string) ($report['status'] ?? 'new'))) ?></span>
                            </header>

                            <p class="admin-feedback-card__message"><?= nl2br($this->escape($report['message'] ?? '')) ?></p>

                            <div class="admin-feedback-card__meta">
                                <span>提交者: <?= $this->escape($report['username'] ?? $report['contact_email'] ?? 'Guest') ?></span>
                                <span><?= $this->escape(substr((string) ($report['created_at'] ?? ''), 0, 16)) ?></span>
                                <?php if (!empty($report['page_url'])): ?>
                                    <a href="<?= $this->escape($report['page_url']) ?>">来源页面</a>
                                <?php endif; ?>
                            </div>

                            <form class="admin-feedback-card__actions" method="post" action="/admin/feedback/<?= $reportId ?>/status">
                                <?php if ($csrfService): ?>
                                    <?= $csrfService->tokenField() ?>
                                <?php endif; ?>
                                <label>
                                    <span class="sr-only">状态</span>
                                    <select name="status">
                                        <?php foreach (['new', 'reviewing', 'resolved'] as $status): ?>
                                            <option value="<?= $this->escape($status) ?>" <?= ($report['status'] ?? '') === $status ? 'selected' : '' ?>><?= $this->escape($statusLabel($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm">更新状态</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
