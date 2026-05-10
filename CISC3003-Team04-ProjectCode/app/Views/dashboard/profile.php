<?php
$layout = 'layouts/main';
$bodyClass = 'page-dashboard';

$errors = $app->session()->getFlash('errors') ?? [];
$old = $app->session()->getFlash('old') ?? [];

$fieldError = static fn (array $items, string $field): ?string => $items[$field][0] ?? null;
$value = static fn (string $field, array $oldValues, array $currentUser, string $fallback = ''): string => (string) ($oldValues[$field] ?? $currentUser[$field] ?? $fallback);
$selectedLocale = $value('locale', $old, $user, 'zh');
?>
<div class="dashboard dashboard--street-sign">
    <aside class="dashboard__sidebar">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-sidebar.php'; ?>
    </aside>

    <main class="dashboard__content">
        <?php include ROOT_PATH . '/app/Views/partials/dashboard-content-nav.php'; ?>
        <section class="dashboard-profile dashboard-surface">
            <?php include ROOT_PATH . '/app/Views/partials/flash-message.php'; ?>

            <header class="dashboard-hero">
                <div>
                    <span class="dashboard-eyebrow"><?= $this->escape($t('dashboard.accountSummary')) ?></span>
                    <h1 class="dashboard-title"><?= $this->escape($t('dashboard.profileTitle')) ?></h1>
                    <p class="dashboard-lead"><?= $this->escape($t('dashboard.profileLead')) ?></p>
                </div>
            </header>

            <div class="dashboard-profile__layout">
                <aside class="dashboard-profile__summary">
                    <div class="dashboard-profile__summary-mark" aria-hidden="true">澳</div>
                    <h2><?= $this->escape($t('dashboard.accountSummary')) ?></h2>
                    <dl>
                        <div>
                            <dt><?= $this->escape($t('dashboard.username')) ?></dt>
                            <dd><?= $this->escape($value('username', $old, $user)) ?></dd>
                        </div>
                        <div>
                            <dt><?= $this->escape($t('dashboard.email')) ?></dt>
                            <dd><?= $this->escape($value('email', $old, $user)) ?></dd>
                        </div>
                        <div>
                            <dt><?= $this->escape($t('dashboard.locale')) ?></dt>
                            <dd><?= $this->escape(strtoupper($selectedLocale)) ?></dd>
                        </div>
                    </dl>
                </aside>

                <article class="dashboard-panel dashboard-profile__card">
                    <form class="dashboard-profile__form" method="post" action="/dashboard/profile">
                        <?= $app->csrf()->tokenField() ?>

                        <?php if ($errors !== []): ?>
                            <div class="dashboard-profile__error-box">
                                <h2 class="dashboard-profile__error-title"><?= $this->escape($t('dashboard.fixIssues')) ?></h2>
                                <ul class="dashboard-profile__error-list">
                                    <?php foreach ($errors as $messages): ?>
                                        <?php foreach ((array) $messages as $message): ?>
                                            <li><?= $this->escape((string) $message) ?></li>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="dashboard-profile__group">
                            <label class="dashboard-profile__label" for="username"><?= $this->escape($t('dashboard.username')) ?></label>
                            <input
                                class="dashboard-profile__input"
                                id="username"
                                name="username"
                                type="text"
                                value="<?= $this->escape($value('username', $old, $user)) ?>"
                                required
                            >
                            <p class="dashboard-profile__hint"><?= $this->escape($t('dashboard.usernameHint')) ?></p>
                            <?php if ($fieldError($errors, 'username')): ?>
                                <p class="dashboard-profile__field-error"><?= $this->escape($fieldError($errors, 'username')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="dashboard-profile__group">
                            <label class="dashboard-profile__label" for="email"><?= $this->escape($t('dashboard.email')) ?></label>
                            <input
                                class="dashboard-profile__input"
                                id="email"
                                name="email"
                                type="email"
                                value="<?= $this->escape($value('email', $old, $user)) ?>"
                                required
                            >
                            <p class="dashboard-profile__hint"><?= $this->escape($t('dashboard.emailHint')) ?></p>
                            <?php if ($fieldError($errors, 'email')): ?>
                                <p class="dashboard-profile__field-error"><?= $this->escape($fieldError($errors, 'email')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="dashboard-profile__group">
                            <label class="dashboard-profile__label" for="locale"><?= $this->escape($t('dashboard.locale')) ?></label>
                            <select class="dashboard-profile__select" id="locale" name="locale">
                                <option value="zh" <?= $selectedLocale === 'zh' ? 'selected' : '' ?>>中文</option>
                                <option value="en" <?= $selectedLocale === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="pt" <?= $selectedLocale === 'pt' ? 'selected' : '' ?>>Português</option>
                            </select>
                            <p class="dashboard-profile__hint"><?= $this->escape($t('dashboard.localeHint')) ?></p>
                            <?php if ($fieldError($errors, 'locale')): ?>
                                <p class="dashboard-profile__field-error"><?= $this->escape($fieldError($errors, 'locale')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="dashboard-profile__actions">
                            <button class="btn btn--primary" type="submit"><?= $this->escape($t('common.saveChanges')) ?></button>
                        </div>
                    </form>
                </article>
            </div>
        </section>
    </main>
</div>
