<?php
$layout = 'layouts/main';
$bodyClass = 'page-auth';
$errors = $app->session()->getFlash('errors') ?? [];
$errorMessage = $app->session()->getFlash('error');
$successMessage = $app->session()->getFlash('success');
$fieldError = static fn (array $items, string $field): ?string => $items[$field][0] ?? null;
$tokenError = $fieldError($errors, 'token');
$passwordError = $fieldError($errors, 'password');
$passwordConfirmError = $fieldError($errors, 'password_confirm');
?>
<section class="auth-page" aria-labelledby="auth-reset-title">
    <div class="auth-page__shell">
        <aside class="auth-page__aside" aria-label="Taste of Macau">
            <span class="auth-page__kicker">Taste of Macau</span>
            <h2 class="auth-page__aside-title" data-i18n="auth.asideTitle"><?= $this->escape($t('auth.asideTitle')) ?></h2>
            <p class="auth-page__aside-text" data-i18n="footer.tagline">Taste of Macau brings together iconic dishes, local neighbourhoods, and food stories across three languages.</p>
            <div class="auth-page__stats" aria-label="Platform snapshot">
                <span><strong data-i18n="landing.stats.dishes.value">20+</strong><small data-i18n="landing.stats.dishes.label">Iconic Dishes</small></span>
                <span><strong data-i18n="landing.stats.categories.value">4</strong><small data-i18n="landing.stats.categories.label">Food Categories</small></span>
                <span><strong data-i18n="landing.stats.languages.value">3</strong><small data-i18n="landing.stats.languages.label">Languages Supported</small></span>
            </div>
        </aside>

        <article class="auth-card auth-card--compact">
        <span class="auth-card__eyebrow" data-i18n="auth.reset.eyebrow">Secure access</span>
        <h1 class="auth-card__title" id="auth-reset-title" data-i18n="auth.reset.title">Set New Password</h1>
        <p class="auth-card__subtitle" data-i18n="auth.reset.subtitle">Choose a strong new password to get back to exploring Macau's food culture.</p>

        <?php if ($errorMessage): ?>
            <div class="auth-card__alert auth-card__alert--error" role="alert"><?= $this->escape($errorMessage) ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="auth-card__alert auth-card__alert--success" role="status"><?= $this->escape($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($tokenError): ?>
            <div class="auth-card__alert auth-card__alert--error" role="alert"><?= $this->escape($tokenError) ?></div>
        <?php endif; ?>

        <form method="post" action="/reset-password" class="auth-card__stack">
            <?= $app->csrf()->tokenField() ?>
            <input type="hidden" name="token" value="<?= $this->escape($token ?? '') ?>">

            <div class="form__group">
                <label class="form__label" for="password" data-i18n="auth.reset.newPassword">New Password</label>
                <input class="form__input" id="password" name="password" type="password" autocomplete="new-password" required aria-invalid="<?= $passwordError ? 'true' : 'false' ?>"<?= $passwordError ? ' aria-describedby="password-error"' : '' ?>>
                <?php if ($passwordError): ?>
                    <p class="form__error" id="password-error"><?= $this->escape($passwordError) ?></p>
                <?php endif; ?>
            </div>

            <div class="form__group">
                <label class="form__label" for="password_confirm" data-i18n="auth.reset.confirmPassword">Confirm New Password</label>
                <input class="form__input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required aria-invalid="<?= $passwordConfirmError ? 'true' : 'false' ?>"<?= $passwordConfirmError ? ' aria-describedby="password-confirm-error"' : '' ?>>
                <?php if ($passwordConfirmError): ?>
                    <p class="form__error" id="password-confirm-error"><?= $this->escape($passwordConfirmError) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--pill auth-card__submit" data-i18n="auth.reset.submit">Reset Password</button>
        </form>
        </article>
    </div>
</section>
