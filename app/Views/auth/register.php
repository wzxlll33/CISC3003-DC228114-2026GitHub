<?php
$layout = 'layouts/main';
$bodyClass = 'page-auth';
$errors = $app->session()->getFlash('errors') ?? [];
$old = $app->session()->getFlash('old') ?? [];
$errorMessage = $app->session()->getFlash('error');
$successMessage = $app->session()->getFlash('success');

$fieldError = static fn (array $items, string $field): ?string => $items[$field][0] ?? null;
$usernameError = $fieldError($errors, 'username');
$emailError = $fieldError($errors, 'email');
$passwordError = $fieldError($errors, 'password');
$passwordConfirmError = $fieldError($errors, 'password_confirm');
?>
<section class="auth-page" aria-labelledby="auth-register-title">
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
            <h1 class="auth-card__title" id="auth-register-title" data-i18n="auth.register.title">Create Your Account</h1>
            <span class="auth-card__eyebrow" data-i18n="auth.register.eyebrow">Create profile</span>
        </div>
        <p class="auth-card__subtitle" data-i18n="auth.register.subtitle">Join Taste of Macau to save favourites, revisit dishes, and unlock your personal food trail.</p>

        <?php if ($errorMessage): ?>
            <div class="auth-card__alert auth-card__alert--error" role="alert"><?= $this->escape($errorMessage) ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="auth-card__alert auth-card__alert--success" role="status"><?= $this->escape($successMessage) ?></div>
        <?php endif; ?>

        <form method="post" action="/register" class="auth-card__stack">
            <?= $app->csrf()->tokenField() ?>

            <div class="form__group">
                <label class="form__label" for="username" data-i18n="auth.username">Username</label>
                <input class="form__input" id="username" name="username" type="text" value="<?= $this->escape($old['username'] ?? '') ?>" autocomplete="username" required aria-invalid="<?= $usernameError ? 'true' : 'false' ?>"<?= $usernameError ? ' aria-describedby="username-error"' : '' ?>>
                <?php if ($usernameError): ?>
                    <p class="form__error" id="username-error"><?= $this->escape($usernameError) ?></p>
                <?php endif; ?>
            </div>

            <div class="form__group">
                <label class="form__label" for="email" data-i18n="auth.email">Email</label>
                <input class="form__input" id="email" name="email" type="email" value="<?= $this->escape($old['email'] ?? '') ?>" autocomplete="email" required aria-invalid="<?= $emailError ? 'true' : 'false' ?>"<?= $emailError ? ' aria-describedby="email-error"' : '' ?>>
                <?php if ($emailError): ?>
                    <p class="form__error" id="email-error"><?= $this->escape($emailError) ?></p>
                <?php endif; ?>
            </div>

            <div class="form__group">
                <label class="form__label" for="password" data-i18n="auth.password">Password</label>
                <input class="form__input" id="password" name="password" type="password" autocomplete="new-password" required aria-invalid="<?= $passwordError ? 'true' : 'false' ?>"<?= $passwordError ? ' aria-describedby="password-error"' : '' ?>>
                <?php if ($passwordError): ?>
                    <p class="form__error" id="password-error"><?= $this->escape($passwordError) ?></p>
                <?php endif; ?>
            </div>

            <div class="form__group">
                <label class="form__label" for="password_confirm" data-i18n="auth.register.confirmPassword">Confirm Password</label>
                <input class="form__input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required aria-invalid="<?= $passwordConfirmError ? 'true' : 'false' ?>"<?= $passwordConfirmError ? ' aria-describedby="password-confirm-error"' : '' ?>>
                <?php if ($passwordConfirmError): ?>
                    <p class="form__error" id="password-confirm-error"><?= $this->escape($passwordConfirmError) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--pill auth-card__submit" data-i18n="auth.register.submit">Create Account</button>
        </form>

        <p class="auth-card__footer">
            <span data-i18n="auth.register.loginPromptText">Already have an account?</span>
            <a href="/login" data-i18n="auth.register.loginLink">Sign In</a>
        </p>
        </article>
    </div>
</section>
