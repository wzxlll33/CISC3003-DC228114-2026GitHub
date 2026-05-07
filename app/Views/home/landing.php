<?php
$layout = 'layouts/main';
$bodyClass = 'page-landing';
?>
<div class="landing-page">
    <section class="landing-hero">
        <div class="landing-page__container landing-hero__grid">
            <div>
                <span class="landing-hero__eyebrow" data-i18n="landing.hero.eyebrow">Macau food discovery platform</span>
                <h1 class="landing-hero__title" data-i18n="landing.hero.title">Discover Macau's Culinary Heritage</h1>
                <p class="landing-hero__subtitle" data-i18n="landing.hero.subtitle">Your guide to 20+ iconic dishes across the city's most beloved neighbourhoods</p>

                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--primary btn btn--primary btn--pill" href="/login" data-i18n="nav.login">登入</a>
                    <a class="landing-btn landing-btn--outline btn btn--outline btn--pill" href="/explore" data-i18n="landing.hero.guestAccess">游客访问</a>
                </div>
            </div>

            <div class="landing-hero__visual" aria-hidden="true">
                <span class="landing-hero__visual-label" data-i18n="landing.hero.visualLabel">Neighbourhood dish collage</span>
                <div class="landing-hero__collage">
                    <article class="landing-hero__tile">
                        <span class="landing-hero__emoji">🥧</span>
                        <div>
                            <p class="landing-hero__tile-title" data-i18n="landing.hero.tile1.title">Portuguese Egg Tart</p>
                            <p class="landing-hero__tile-text" data-i18n="landing.hero.tile1.text">Warm pastry classics from the streets of Coloane.</p>
                        </div>
                    </article>
                    <article class="landing-hero__tile">
                        <span class="landing-hero__emoji">🍢</span>
                        <div>
                            <p class="landing-hero__tile-title" data-i18n="landing.hero.tile2.title">Street Skewers</p>
                            <p class="landing-hero__tile-text" data-i18n="landing.hero.tile2.text">Late-night snacks near every bustling avenue.</p>
                        </div>
                    </article>
                    <article class="landing-hero__tile">
                        <span class="landing-hero__emoji">🥘</span>
                        <div>
                            <p class="landing-hero__tile-title" data-i18n="landing.hero.tile3.title">Macanese Classics</p>
                            <p class="landing-hero__tile-text" data-i18n="landing.hero.tile3.text">Blended flavors shaped by centuries of heritage.</p>
                        </div>
                    </article>
                    <article class="landing-hero__tile">
                        <span class="landing-hero__emoji">🍮</span>
                        <div>
                            <p class="landing-hero__tile-title" data-i18n="landing.hero.tile4.title">Dessert Trails</p>
                            <p class="landing-hero__tile-text" data-i18n="landing.hero.tile4.text">Sweet stops for almond cookies and pudding cups.</p>
                        </div>
                    </article>
                    <article class="landing-hero__tile">
                        <span class="landing-hero__emoji">🫖</span>
                        <div>
                            <p class="landing-hero__tile-title" data-i18n="landing.hero.tile5.title">Tea & Cafés</p>
                            <p class="landing-hero__tile-text" data-i18n="landing.hero.tile5.text">Hidden neighbourhood spots for slow afternoon breaks.</p>
                        </div>
                    </article>
                    <article class="landing-hero__tile">
                        <span class="landing-hero__emoji">📍</span>
                        <div>
                            <p class="landing-hero__tile-title" data-i18n="landing.hero.tile6.title">Live Map Links</p>
                            <p class="landing-hero__tile-text" data-i18n="landing.hero.tile6.text">Jump from discovery cards to the exact place to visit.</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-features">
        <div class="landing-page__container">
            <div class="landing-section__header">
                <span class="landing-features__eyebrow" data-i18n="landing.features.eyebrow">Platform features</span>
                <h2 class="landing-section__title" data-i18n="landing.features.title">Everything You Need to Explore</h2>
                <p class="landing-features__lead" data-i18n="landing.features.subtitle">Taste of Macau combines curated discovery, multilingual search, and personal tracking in one service-driven experience.</p>
            </div>

            <div class="landing-features__grid">
                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon">🗺️</span>
                    <h3 class="landing-feature-card__title" data-i18n="landing.features.map.title">Interactive Map</h3>
                    <p class="landing-feature-card__text" data-i18n="landing.features.map.text">Find dishes on a real-time map of Macau with bi-directional card linking</p>
                </article>

                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon">🔍</span>
                    <h3 class="landing-feature-card__title" data-i18n="landing.features.search.title">Smart Search</h3>
                    <p class="landing-feature-card__text" data-i18n="landing.features.search.text">Search by name, category, or location in Chinese, English, or Portuguese</p>
                </article>

                <article class="landing-feature-card">
                    <span class="landing-feature-card__icon">❤️</span>
                    <h3 class="landing-feature-card__title" data-i18n="landing.features.collection.title">Personal Collection</h3>
                    <p class="landing-feature-card__text" data-i18n="landing.features.collection.text">Save your favorites and track your food exploration journey</p>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-stats">
        <div class="landing-page__container">
            <div class="landing-section__header">
                <span class="landing-stats__eyebrow" data-i18n="landing.stats.eyebrow">Platform snapshot</span>
            </div>

            <div class="landing-stats__grid">
                <article class="landing-stat">
                    <span class="landing-stat__value" data-i18n="landing.stats.dishes.value">20+</span>
                    <span class="landing-stat__label" data-i18n="landing.stats.dishes.label">Iconic Dishes</span>
                </article>
                <article class="landing-stat">
                    <span class="landing-stat__value" data-i18n="landing.stats.categories.value">4</span>
                    <span class="landing-stat__label" data-i18n="landing.stats.categories.label">Food Categories</span>
                </article>
                <article class="landing-stat">
                    <span class="landing-stat__value" data-i18n="landing.stats.languages.value">3</span>
                    <span class="landing-stat__label" data-i18n="landing.stats.languages.label">Languages Supported</span>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-categories">
        <div class="landing-page__container">
            <div class="landing-section__header">
                <span class="landing-categories__eyebrow" data-i18n="landing.categories.eyebrow">Curated browsing</span>
                <h2 class="landing-section__title" data-i18n="landing.categories.title">Explore by Category</h2>
                <p class="landing-categories__lead" data-i18n="landing.categories.subtitle">Move from street stalls to heritage cafés with themed collections built for first-time visitors and returning locals alike.</p>
            </div>

            <div class="landing-categories__grid">
                <article class="landing-category-card">
                    <span class="landing-category-card__icon">🍢</span>
                    <h3 class="landing-category-card__title" data-i18n="landing.categories.street.title">Street Snacks</h3>
                    <p class="landing-category-card__text" data-i18n="landing.categories.street.text">Discover pepper buns, skewers, and grab-and-go favorites from Macau's busiest pedestrian streets.</p>
                </article>
                <article class="landing-category-card">
                    <span class="landing-category-card__icon">🍷</span>
                    <h3 class="landing-category-card__title" data-i18n="landing.categories.portuguese.title">Portuguese</h3>
                    <p class="landing-category-card__text" data-i18n="landing.categories.portuguese.text">Browse dishes inspired by Lusophone heritage, from bacalhau plates to comforting Macanese fusion meals.</p>
                </article>
                <article class="landing-category-card">
                    <span class="landing-category-card__icon">🍰</span>
                    <h3 class="landing-category-card__title" data-i18n="landing.categories.desserts.title">Desserts</h3>
                    <p class="landing-category-card__text" data-i18n="landing.categories.desserts.text">Follow a sweet route through egg tarts, almond cookies, puddings, and café treats across the city.</p>
                </article>
                <article class="landing-category-card">
                    <span class="landing-category-card__icon">🍲</span>
                    <h3 class="landing-category-card__title" data-i18n="landing.categories.main.title">Main Dishes</h3>
                    <p class="landing-category-card__text" data-i18n="landing.categories.main.text">Plan full meals with rice dishes, noodles, curries, and richly layered specialities worth crossing town for.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-cta">
        <div class="landing-page__container">
            <div class="landing-cta__panel">
                <span class="landing-cta__eyebrow" data-i18n="landing.cta.eyebrow">Start your food trail</span>
                <h2 class="landing-cta__title" data-i18n="landing.cta.title">Ready to Explore Macau's Flavors?</h2>
                <p class="landing-cta__text" data-i18n="landing.cta.subtitle">Join thousands of food lovers discovering the best of Macau</p>
                <div class="landing-cta__actions">
                    <a class="landing-btn landing-btn--primary btn btn--primary btn--pill" href="/register" data-i18n="landing.cta.button">Create Free Account</a>
                    <a class="landing-btn landing-btn--outline-dark btn btn--outline btn--pill" href="/login" data-i18n="landing.cta.secondary">Sign In</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="landing-footer">
        <div class="landing-page__container">
            <div class="landing-footer__grid">
                <div>
                    <h3 class="landing-footer__title" data-i18n="landing.footer.aboutTitle">About</h3>
                    <p class="landing-footer__text" data-i18n="landing.footer.aboutText">Taste of Macau helps visitors and locals explore iconic dishes, neighbourhood stories, and multilingual food discovery in one place.</p>
                </div>
                <div>
                    <h3 class="landing-footer__title" data-i18n="landing.footer.quickLinksTitle">Quick Links</h3>
                    <div class="landing-footer__list">
                        <a class="landing-footer__link" href="/register" data-i18n="landing.footer.quickLinks.register">Create Account</a>
                        <a class="landing-footer__link" href="/login" data-i18n="landing.footer.quickLinks.login">Sign In</a>
                        <a class="landing-footer__link" href="/explore" data-i18n="landing.footer.quickLinks.explore">Explore Platform</a>
                    </div>
                </div>
                <div>
                    <h3 class="landing-footer__title" data-i18n="landing.footer.languagesTitle">Languages</h3>
                    <div class="landing-footer__list">
                        <span class="landing-footer__text" data-i18n="landing.footer.languages.zh">中文</span>
                        <span class="landing-footer__text" data-i18n="landing.footer.languages.en">English</span>
                        <span class="landing-footer__text" data-i18n="landing.footer.languages.pt">Português</span>
                    </div>
                </div>
            </div>

            <div class="landing-footer__bottom">
                <span class="landing-footer__meta" data-i18n="landing.footer.course">CISC3003 Web Programming — Team 04</span>
                <span class="landing-footer__meta">© <?= date('Y') ?> Taste of Macau</span>
            </div>
        </div>
    </footer>
</div>
