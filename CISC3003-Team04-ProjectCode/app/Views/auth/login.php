<?php
$layout = 'layouts/main';
$bodyClass = 'page-auth';
$errors = $app->session()->getFlash('errors') ?? [];
$old = $app->session()->getFlash('old') ?? [];
$errorMessage = $app->session()->getFlash('error');
$successMessage = $app->session()->getFlash('success');

$fieldError = static fn (array $items, string $field): ?string => $items[$field][0] ?? null;
$emailError = $fieldError($errors, 'email');
$passwordError = $fieldError($errors, 'password');
?>
<section class="auth-page" aria-labelledby="auth-login-title">
    <div class="auth-page__shell">
        <aside class="auth-page__aside" aria-label="Taste of Macau">
            <img class="auth-page__kicker-logo" src="/assets/images/icon.png" alt="Taste of Macau">
            <h2 class="auth-page__aside-title" data-i18n="auth.asideTitle"><?= $this->escape($t('auth.asideTitle')) ?></h2>
            <p class="auth-page__aside-text" data-i18n="footer.tagline">Taste of Macau brings together iconic dishes, local neighbourhoods, and food stories across three languages.</p>
            <div class="auth-page__stats" aria-label="Platform snapshot">
                <span><strong data-i18n="landing.stats.dishes.value">20+</strong><small data-i18n="landing.stats.dishes.label">Iconic Dishes</small></span>
                <span><strong data-i18n="landing.stats.categories.value">4</strong><small data-i18n="landing.stats.categories.label">Food Categories</small></span>
                <span><strong data-i18n="landing.stats.languages.value">3</strong><small data-i18n="landing.stats.languages.label">Languages Supported</small></span>
            </div>
        </aside>

        <article class="auth-card">
        <div class="auth-card__header">
            <h1 class="auth-card__title" id="auth-login-title" data-i18n="auth.login.title">Welcome Back</h1>
            <span class="auth-card__eyebrow" data-i18n="auth.login.eyebrow">Taste of Macau</span>
        </div>
        <p class="auth-card__subtitle" data-i18n="auth.login.subtitle">Sign in to continue your culinary journey across Macau's most iconic dishes.</p>

        <?php if ($errorMessage): ?>
            <div class="auth-card__alert auth-card__alert--error" role="alert"><?= $this->escape($errorMessage) ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="auth-card__alert auth-card__alert--success" role="status"><?= $this->escape($successMessage) ?></div>
        <?php endif; ?>

        <form method="post" action="/login" class="auth-card__stack">
            <?= $app->csrf()->tokenField() ?>

            <div class="form__group">
                <label class="form__label" for="email" data-i18n="auth.email">Email</label>
                <input class="form__input" id="email" name="email" type="email" value="<?= $this->escape($old['email'] ?? '') ?>" autocomplete="email" required aria-invalid="<?= $emailError ? 'true' : 'false' ?>"<?= $emailError ? ' aria-describedby="email-error"' : '' ?>>
                <?php if ($emailError): ?>
                    <p class="form__error" id="email-error"><?= $this->escape($emailError) ?></p>
                <?php endif; ?>
            </div>

            <div class="form__group">
                <label class="form__label" for="password" data-i18n="auth.password">Password</label>
                <input class="form__input" id="password" name="password" type="password" autocomplete="current-password" required aria-invalid="<?= $passwordError ? 'true' : 'false' ?>"<?= $passwordError ? ' aria-describedby="password-error"' : '' ?>>
                <?php if ($passwordError): ?>
                    <p class="form__error" id="password-error"><?= $this->escape($passwordError) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--pill auth-card__submit" data-i18n="auth.login.submit">Sign In</button>

            <div class="auth-card__actions">
                <a class="auth-card__aux-link" href="/forgot-password" data-i18n="auth.login.forgot">Forgot password?</a>
            </div>
        </form>

        <p class="auth-card__footer">
            <span data-i18n="auth.login.registerPromptText">Don't have an account?</span>
            <a href="/register" data-i18n="auth.login.registerLink">Register</a>
        </p>
        </article>
    </div>
</section>
