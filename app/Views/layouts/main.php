<?php
$resolvedLocale = in_array($locale ?? null, ['zh', 'en', 'pt'], true) ? (string) $locale : 'zh';
$siteNames = [
    'zh' => '味遊澳門',
    'en' => 'Taste of Macau',
    'pt' => 'Sabores de Macau',
];
$titleMap = [
    'Discover Macau\'s Culinary Heritage' => [
        'zh' => '探索澳門經典美食 · 味遊澳門',
        'en' => 'Discover Macau\'s Culinary Heritage · Taste of Macau',
        'pt' => 'Descobre a gastronomia de Macau · Sabores de Macau',
    ],
    'Taste of Macau' => [
        'zh' => '味遊澳門',
        'en' => 'Taste of Macau',
        'pt' => 'Sabores de Macau',
    ],
    'Welcome Back' => [
        'zh' => '登入 · 味遊澳門',
        'en' => 'Welcome Back · Taste of Macau',
        'pt' => 'Entrar · Sabores de Macau',
    ],
    'Create Your Account' => [
        'zh' => '建立帳戶 · 味遊澳門',
        'en' => 'Create Your Account · Taste of Macau',
        'pt' => 'Criar conta · Sabores de Macau',
    ],
    'Reset Your Password' => [
        'zh' => '重設密碼 · 味遊澳門',
        'en' => 'Reset Your Password · Taste of Macau',
        'pt' => 'Repor palavra-passe · Sabores de Macau',
    ],
    'Set New Password' => [
        'zh' => '設定新密碼 · 味遊澳門',
        'en' => 'Set New Password · Taste of Macau',
        'pt' => 'Definir nova palavra-passe · Sabores de Macau',
    ],
    'Dashboard 路 Taste of Macau' => [
        'zh' => '我的總覽 · 味遊澳門',
        'en' => 'Dashboard · Taste of Macau',
        'pt' => 'Painel · Sabores de Macau',
    ],
    'Profile 路 Taste of Macau' => [
        'zh' => '個人資料 · 味遊澳門',
        'en' => 'Profile · Taste of Macau',
        'pt' => 'Perfil · Sabores de Macau',
    ],
    'Favorites 路 Taste of Macau' => [
        'zh' => '我的最愛 · 味遊澳門',
        'en' => 'Favorites · Taste of Macau',
        'pt' => 'Favoritos · Sabores de Macau',
    ],
    'Search History 路 Taste of Macau' => [
        'zh' => '搜尋紀錄 · 味遊澳門',
        'en' => 'Search History · Taste of Macau',
        'pt' => 'Histórico de pesquisa · Sabores de Macau',
    ],
    'Browse History 路 Taste of Macau' => [
        'zh' => '瀏覽紀錄 · 味遊澳門',
        'en' => 'Browse History · Taste of Macau',
        'pt' => 'Histórico de navegação · Sabores de Macau',
    ],
];
$rawTitle = (string) ($title ?? $siteNames[$resolvedLocale]);
$pageTitle = $titleMap[$rawTitle][$resolvedLocale] ?? str_replace(
    ['Taste of Macau', ' 路 ', ' — ', ' - '],
    [$siteNames[$resolvedLocale], ' · ', ' · ', ' · '],
    $rawTitle
);
$localeNames = [
    'zh' => '中文',
    'en' => 'English',
    'pt' => 'Português',
];
$guestAccessLabels = [
    'zh' => '游客访问',
    'en' => 'Guest access',
    'pt' => 'Acesso de visitante',
];
$feedbackLabels = [
    'zh' => [
        'open' => '回報問題',
        'title' => '回報店家資料問題',
        'subtitle' => '店舖不存在、地址有誤或資料需要更新，都可以在這裡告訴我們。',
        'context' => '目前頁面',
        'issueType' => '問題類型',
        'missingStore' => '找不到這間店',
        'wrongAddress' => '地址或地圖位置有誤',
        'closed' => '店舖已結業',
        'wrongInfo' => '電話、時間或資料有誤',
        'other' => '其他問題',
        'message' => '補充說明',
        'messagePlaceholder' => '請描述你現場看到的情況，例如正確地址、店名變更或需要補充的資料。',
        'contactEmail' => '聯絡電郵（選填）',
        'cancel' => '取消',
        'submit' => '提交回饋',
        'success' => '已收到你的回饋，謝謝協助我們更新資料。',
        'error' => '暫時無法提交回饋，請稍後再試。',
        'validation' => '請選擇問題類型，並輸入至少 10 個字元。',
    ],
    'en' => [
        'open' => 'Report issue',
        'title' => 'Report a restaurant data issue',
        'subtitle' => 'Tell us if a shop is missing, the address is wrong, or the listing needs an update.',
        'context' => 'Current page',
        'issueType' => 'Issue type',
        'missingStore' => 'Could not find this shop',
        'wrongAddress' => 'Wrong address or map pin',
        'closed' => 'Shop is closed',
        'wrongInfo' => 'Phone, hours, or details are wrong',
        'other' => 'Other issue',
        'message' => 'Details',
        'messagePlaceholder' => 'Describe what you found on site, such as the correct address, name change, or missing details.',
        'contactEmail' => 'Contact email (optional)',
        'cancel' => 'Cancel',
        'submit' => 'Submit feedback',
        'success' => 'Feedback received. Thanks for helping us keep the data accurate.',
        'error' => 'Unable to submit feedback right now. Please try again later.',
        'validation' => 'Choose an issue type and enter at least 10 characters.',
    ],
    'pt' => [
        'open' => 'Reportar problema',
        'title' => 'Reportar problema nos dados do restaurante',
        'subtitle' => 'Avise-nos se a loja não existe, o endereço está errado ou a ficha precisa de atualização.',
        'context' => 'Página atual',
        'issueType' => 'Tipo de problema',
        'missingStore' => 'Não encontrei esta loja',
        'wrongAddress' => 'Endereço ou marcador incorreto',
        'closed' => 'A loja encerrou',
        'wrongInfo' => 'Telefone, horário ou detalhes incorretos',
        'other' => 'Outro problema',
        'message' => 'Detalhes',
        'messagePlaceholder' => 'Descreva o que encontrou no local, como o endereço correto, mudança de nome ou dados em falta.',
        'contactEmail' => 'Email de contacto (opcional)',
        'cancel' => 'Cancelar',
        'submit' => 'Enviar feedback',
        'success' => 'Feedback recebido. Obrigado por nos ajudar a manter os dados corretos.',
        'error' => 'Não foi possível enviar o feedback agora. Tente novamente mais tarde.',
        'validation' => 'Escolha um tipo de problema e escreva pelo menos 10 caracteres.',
    ],
][$resolvedLocale];
?>
<!DOCTYPE html>
<html lang="<?= $this->escape($resolvedLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->escape($pageTitle) ?></title>
    <meta name="csrf-token" content="<?= $this->escape($csrfToken ?? '') ?>">
    <meta name="app-locale" content="<?= $this->escape($resolvedLocale) ?>">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/pages.css">
    <link rel="stylesheet" href="/assets/css/utilities.css">
    <link rel="icon" type="image/png" href="/assets/images/Webpage_logo.png">
    <link rel="apple-touch-icon" href="/assets/images/Webpage_logo.png">
    <?= $extraHead ?? '' ?>
</head>
<body class="<?= $this->escape($bodyClass ?? 'page-app') ?>">
    <?php
    $resolvedBodyClass = $bodyClass ?? 'page-app';
    $session = isset($app) && is_object($app) && method_exists($app, 'session') ? $app->session() : null;
    $isLoggedIn = $session && method_exists($session, 'isLoggedIn') ? $session->isLoggedIn() : false;
    $username = $session && method_exists($session, 'get') ? (string) $session->get('username', 'Explorer') : 'Explorer';
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $brandHref = $isLoggedIn ? '/explore' : '/';
    $homeHref = '/';
    $isExplorePage = $currentPath === '/explore';
    $isRestaurantDetailPage = str_starts_with($currentPath, '/restaurant/');
    $isFoodDetailPage = str_starts_with($currentPath, '/food/');
    $isMemberArea = str_starts_with($currentPath, '/dashboard') || str_starts_with($currentPath, '/admin');
    $isGuestFavoritesPage = !$isLoggedIn && $isExplorePage && (string) ($_GET['favorites'] ?? '') === '1';
    $favoritesHref = $isLoggedIn ? '/dashboard/favorites' : '/explore?favorites=1';
    $isFavoritesActive = $isLoggedIn ? $currentPath === '/dashboard/favorites' : $isGuestFavoritesPage;
    ?>
    <script>
        window.__APP_CONTEXT__ = {
            locale: <?= json_encode($resolvedLocale, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            currentPath: <?= json_encode($currentPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            isLoggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>,
            username: <?= json_encode($username, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            feedbackLabels: <?= json_encode($feedbackLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>

    <a class="skip-link" href="#main-content"><?= $this->escape($t('common.skipToContent')) ?></a>

    <header class="site-header<?= $currentPath === '/explore' ? ' site-header--explore' : '' ?>">
        <div class="site-header__inner">
            <a class="site-brand" href="<?= $brandHref ?>">
                <span class="site-brand__mark" aria-hidden="true">
                    <img class="site-brand__logo" src="/assets/images/icon.png" alt="">
                </span>
                <span class="site-brand__text">
                    <span class="site-brand__title">Taste of Macau</span>
                    <span class="site-brand__subtitle">味遊澳門 · Sabores de Macau</span>
                </span>
            </a>

            <nav class="site-nav" aria-label="<?= $this->escape($t('nav.primary')) ?>">
                <?php if ($isRestaurantDetailPage || $isFoodDetailPage): ?>
                    <a class="site-nav__back-action" href="/explore" aria-label="<?= $this->escape($isRestaurantDetailPage ? $t('restaurantDetail.backToExplore') : $t('foodDetail.backToCatalog')) ?>">
                        <span aria-hidden="true">&larr;</span>
                        <span><?= $this->escape($isRestaurantDetailPage ? $t('restaurantDetail.backToExplore') : $t('foodDetail.backToCatalog')) ?></span>
                    </a>
                <?php endif; ?>

                <div class="site-nav__links">
                    <?php if ($isLoggedIn): ?>
                        <a class="site-nav__link<?= $currentPath === '/explore' ? ' site-nav__link--active' : '' ?>" href="/explore" data-i18n="nav.explore"<?= $currentPath === '/explore' ? ' aria-current="page"' : '' ?>><?= $this->escape($t('nav.explore')) ?></a>
                        <a class="site-nav__link<?= $isMemberArea ? ' site-nav__link--active' : '' ?>" href="/dashboard" data-i18n="nav.dashboard"<?= $isMemberArea ? ' aria-current="page"' : '' ?>><?= $this->escape($t('nav.dashboard')) ?></a>
                    <?php else: ?>
                        <a class="site-nav__link<?= $currentPath === '/explore' && !$isGuestFavoritesPage ? ' site-nav__link--active' : '' ?>" href="/explore" data-i18n="nav.explore"<?= $currentPath === '/explore' && !$isGuestFavoritesPage ? ' aria-current="page"' : '' ?>><?= $this->escape($t('nav.explore')) ?></a>
                        <a class="site-nav__link<?= $currentPath === '/login' ? ' site-nav__link--active' : '' ?>" href="/login" data-i18n="nav.dashboard"<?= $currentPath === '/login' ? ' aria-current="page"' : '' ?>><?= $this->escape($t('nav.dashboard')) ?></a>
                    <?php endif; ?>
                </div>

                <div class="site-nav__actions">
                    <?php if ($isExplorePage): ?>
                        <button
                            type="button"
                            class="site-nav__search-toggle"
                            data-explore-search-toggle
                            aria-label="<?= $this->escape($t('explore.searchLabel')) ?>"
                            aria-controls="restaurant-search"
                            aria-expanded="true"
                        >
                            <span class="site-nav__search-glyph" aria-hidden="true"></span>
                        </button>
                    <?php endif; ?>
                    <a
                        class="site-nav__icon-action<?= $isFavoritesActive ? ' site-nav__icon-action--active' : '' ?>"
                        href="<?= $this->escape($favoritesHref) ?>"
                        aria-label="<?= $this->escape($t('nav.favorites')) ?>"
                        title="<?= $this->escape($t('nav.favorites')) ?>"
                        <?= $isFavoritesActive ? 'aria-current="page"' : '' ?>
                    >
                        <span aria-hidden="true">&#9829;</span>
                        <span class="sr-only"><?= $this->escape($t('nav.favorites')) ?></span>
                    </a>
                </div>

                <div class="site-nav__language-menu site-nav__language-menu--global" data-mobile-locale-menu>
                    <button
                        type="button"
                        class="site-nav__globe"
                        data-mobile-locale-toggle
                        aria-label="<?= $this->escape($t('nav.languages')) ?>"
                        aria-expanded="false"
                    >
                        <span aria-hidden="true">&#127760;</span>
                        <span class="sr-only"><?= $this->escape($t('nav.languages')) ?></span>
                    </button>
                    <div class="site-nav__language-popover" data-mobile-locale-popover hidden>
                        <?php foreach ($localeNames as $localeCode => $localeLabel): ?>
                            <button
                                type="button"
                                class="site-nav__mobile-locale<?= $resolvedLocale === $localeCode ? ' is-active' : '' ?>"
                                data-locale-switch
                                data-locale="<?= $this->escape($localeCode) ?>"
                                aria-pressed="<?= $resolvedLocale === $localeCode ? 'true' : 'false' ?>"
                            ><?= $this->escape($localeLabel) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="site-main" tabindex="-1">
        <?= $content ?? '' ?>
    </main>

    <div
        class="feedback-modal"
        data-feedback-modal
        data-feedback-success="<?= $this->escape($feedbackLabels['success']) ?>"
        data-feedback-error="<?= $this->escape($feedbackLabels['error']) ?>"
        data-feedback-validation="<?= $this->escape($feedbackLabels['validation']) ?>"
        hidden
    >
        <button type="button" class="feedback-modal__backdrop" data-feedback-close aria-label="<?= $this->escape($feedbackLabels['cancel']) ?>"></button>
        <section class="feedback-modal__panel" role="dialog" aria-modal="true" aria-labelledby="feedback-modal-title">
            <header class="feedback-modal__header">
                <div>
                    <h2 id="feedback-modal-title"><?= $this->escape($feedbackLabels['title']) ?></h2>
                    <p><?= $this->escape($feedbackLabels['subtitle']) ?></p>
                </div>
                <button type="button" class="feedback-modal__close" data-feedback-close aria-label="<?= $this->escape($feedbackLabels['cancel']) ?>">×</button>
            </header>

            <form class="feedback-modal__form" data-feedback-form>
                <input type="hidden" name="restaurant_id" data-feedback-restaurant-id>
                <input type="hidden" name="food_id" data-feedback-food-id>
                <input type="hidden" name="context_type" data-feedback-context-type>

                <div class="feedback-modal__context">
                    <span><?= $this->escape($feedbackLabels['context']) ?></span>
                    <strong data-feedback-context><?= $this->escape($pageTitle) ?></strong>
                </div>

                <label class="feedback-modal__field">
                    <span><?= $this->escape($feedbackLabels['issueType']) ?></span>
                    <select name="issue_type" required>
                        <option value="missing_store"><?= $this->escape($feedbackLabels['missingStore']) ?></option>
                        <option value="wrong_address"><?= $this->escape($feedbackLabels['wrongAddress']) ?></option>
                        <option value="closed"><?= $this->escape($feedbackLabels['closed']) ?></option>
                        <option value="wrong_info"><?= $this->escape($feedbackLabels['wrongInfo']) ?></option>
                        <option value="other"><?= $this->escape($feedbackLabels['other']) ?></option>
                    </select>
                </label>

                <label class="feedback-modal__field">
                    <span><?= $this->escape($feedbackLabels['message']) ?></span>
                    <textarea name="message" rows="5" minlength="10" required placeholder="<?= $this->escape($feedbackLabels['messagePlaceholder']) ?>"></textarea>
                </label>

                <label class="feedback-modal__field">
                    <span><?= $this->escape($feedbackLabels['contactEmail']) ?></span>
                    <input type="email" name="contact_email" autocomplete="email">
                </label>

                <div class="feedback-modal__status" data-feedback-status hidden></div>

                <footer class="feedback-modal__actions">
                    <button type="button" class="btn btn--outline" data-feedback-close><?= $this->escape($feedbackLabels['cancel']) ?></button>
                    <button type="submit" class="btn btn--primary"><?= $this->escape($feedbackLabels['submit']) ?></button>
                </footer>
            </form>
        </section>
    </div>

    <?php if ($resolvedBodyClass !== 'page-landing'): ?>
        <footer class="site-footer">
            <div class="site-footer__inner" data-i18n="footer.tagline">
                <?= $this->escape($t('footer.tagline')) ?>
            </div>
        </footer>
    <?php endif; ?>

    <?php
    $inlineScript = static function (string $path): void {
        $scriptPath = ROOT_PATH . '/public' . $path;

        if (!is_file($scriptPath)) {
            return;
        }

        echo "<script>\n";
        readfile($scriptPath);
        echo "\n</script>\n";
    };

    $inlineScript('/assets/js/api.js');
    $inlineScript('/assets/js/i18n.js');
    $inlineScript('/assets/js/favorites.js');

    if ($resolvedBodyClass === 'page-app') {
        $inlineScript('/assets/js/food-catalog.js');
        $inlineScript('/assets/js/restaurant-catalog.js');
        $inlineScript('/assets/js/review.js');
        $inlineScript('/assets/js/search.js');
    }
    ?>
    <?= $extraScripts ?? '' ?>
    <?php $inlineScript('/assets/js/app.js'); ?>
</body>
</html>
