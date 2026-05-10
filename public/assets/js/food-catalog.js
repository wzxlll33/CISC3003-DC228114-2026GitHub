(() => {
    class FoodCatalog {
        constructor(options = {}) {
            this.root = options.root;
            this.grid = this.root?.querySelector(options.gridSelector || '#food-cards-grid');
            this.countElement = this.root?.querySelector(options.countSelector || '[data-results-count]');
            this.noResultsElement = this.root?.querySelector(options.noResultsSelector || '#catalog-no-results');
            this.nearMeToggle = this.root?.querySelector('[data-near-me-toggle]');
            this.activeCategory = 'all';
            this.searchTerm = '';
            this.isSearchMode = false;
            this.allFoods = window.FavoriteStore?.applyToFoods(this.getBootstrapFoods()) || this.getBootstrapFoods();
            this.currentFoods = [...this.allFoods];
            this.visibleFoods = [];
            this.nearMeState = {
                active: false,
                lat: null,
                lng: null,
            };

            if (!this.root || !this.grid) {
                return;
            }

            this.bindCategoryPills();
            this.bindCardClicks();
            this.bindCardHover();
            this.bindFavoriteToggles();
            this.bindNearMeToggle();
            this.bindFavoriteSync();
            this.refreshView();
        }

        static init(options = {}) {
            return new FoodCatalog(options);
        }

        getBootstrapFoods() {
            if (window.__TASTE_OF_MACAU__ && Array.isArray(window.__TASTE_OF_MACAU__.foods)) {
                return window.__TASTE_OF_MACAU__.foods;
            }

            const serializedFoods = this.root?.dataset?.foods;

            if (!serializedFoods) {
                return [];
            }

            try {
                const parsed = JSON.parse(serializedFoods);
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        bindCategoryPills() {
            const pills = this.root.querySelectorAll('[data-category-pill]');

            pills.forEach((pill) => {
                pill.addEventListener('click', () => {
                    const slug = pill.dataset.categorySlug || 'all';
                    this.setActiveCategory(slug);
                });
            });
        }

        bindCardClicks() {
            this.grid.addEventListener('click', (event) => {
                if (event.target.closest('[data-favorite-toggle]')) {
                    return;
                }

                const card = event.target.closest('[data-food-card]');

                if (!card) {
                    return;
                }

                const foodId = card.dataset.foodId;
                const detailUrl = card.dataset.detailUrl;

                if (foodId && window.macauMap) {
                    window.macauMap.flyToFood(foodId);
                }

                if (detailUrl) {
                    window.setTimeout(() => {
                        window.location.href = detailUrl;
                    }, 220);
                }
            });
        }

        bindCardHover() {
            this.grid.addEventListener('mouseover', (event) => {
                const card = event.target.closest('[data-food-card]');

                if (!card || !card.dataset.foodId || !window.macauMap) {
                    return;
                }

                window.macauMap.highlightMarker(card.dataset.foodId, 900);
            });
        }

        bindFavoriteToggles() {
            this.grid.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-favorite-toggle]');

                if (!button) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const foodId = button.dataset.foodId;

                if (!foodId || (!window.api && !window.FavoriteStore)) {
                    return;
                }

                button.disabled = true;

                try {
                    const payload = window.FavoriteStore
                        ? await window.FavoriteStore.toggle(foodId)
                        : await window.api.post(`/api/favorites/${encodeURIComponent(foodId)}`);
                    const isFavorited = payload?.action === 'added';
                    this.updateFavoriteState(foodId, isFavorited);
                    this.animateFavoriteButton(button, isFavorited);
                } catch (error) {
                    console.error(error);
                } finally {
                    button.disabled = false;
                }
            });
        }

        bindFavoriteSync() {
            document.addEventListener('favorites:updated', (event) => {
                const favoriteIds = new Set(
                    (Array.isArray(event.detail?.favoriteIds) ? event.detail.favoriteIds : [])
                        .map((id) => String(id))
                );
                const apply = (food) => ({ ...food, is_favorited: favoriteIds.has(String(food.id)) });

                this.allFoods = this.allFoods.map(apply);
                this.currentFoods = this.currentFoods.map(apply);
                this.visibleFoods = this.visibleFoods.map(apply);
                this.refreshView();
            });
        }

        bindNearMeToggle() {
            if (!this.nearMeToggle) {
                return;
            }

            this.nearMeToggle.addEventListener('click', () => {
                if (this.nearMeState.active) {
                    this.resetSort();
                    return;
                }

                if (!navigator.geolocation) {
                    window.alert(window.I18n?.t('messages.geolocationFailed') || 'Unable to get your location.');
                    return;
                }

                const originalLabel = window.I18n?.t('actions.nearMe') || 'Near Me';
                this.nearMeToggle.disabled = true;
                this.nearMeToggle.textContent = window.I18n?.t('actions.locating') || 'Locating...';

                navigator.geolocation.getCurrentPosition((position) => {
                    this.nearMeToggle.disabled = false;
                    this.nearMeToggle.textContent = originalLabel;
                    this.sortByDistance(position.coords.latitude, position.coords.longitude);
                }, () => {
                    this.nearMeToggle.disabled = false;
                    this.nearMeToggle.textContent = originalLabel;
                    window.alert(window.I18n?.t('messages.geolocationFailed') || 'Unable to get your location.');
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000,
                });
            });
        }

        setActiveCategory(slug) {
            this.activeCategory = slug || 'all';
            this.syncActivePill();
            this.refreshView();
            document.dispatchEvent(new CustomEvent('catalog:category-change', {
                detail: {
                    slug: this.activeCategory,
                    searchTerm: this.searchTerm,
                },
            }));
        }

        getActiveCategory() {
            return this.activeCategory;
        }

        setSearchState(term, isSearchMode) {
            this.searchTerm = term || '';
            this.isSearchMode = Boolean(isSearchMode);
        }

        filterByCategory(slug) {
            this.activeCategory = slug || 'all';
            this.syncActivePill();
            this.refreshView();
        }

        applySearchResults(foods, query) {
            this.setSearchState(query, true);
            this.currentFoods = Array.isArray(foods) ? foods : [];
            this.refreshView();
        }

        clearSearch() {
            this.setSearchState('', false);
            this.currentFoods = [...this.allFoods];
            this.refreshView();
        }

        sortByDistance(userLat, userLng) {
            this.nearMeState = {
                active: true,
                lat: Number(userLat),
                lng: Number(userLng),
            };

            this.nearMeToggle?.classList.add('is-active');
            this.refreshView();

            if (window.macauMap) {
                window.macauMap.showUserLocation(this.nearMeState.lat, this.nearMeState.lng);
            }
        }

        resetSort() {
            this.nearMeState = {
                active: false,
                lat: null,
                lng: null,
            };

            this.nearMeToggle?.classList.remove('is-active');
            this.refreshView();

            if (window.macauMap) {
                window.macauMap.removeUserLocation();
            }
        }

        refreshView() {
            const sourceFoods = this.isSearchMode ? this.currentFoods : this.allFoods;
            const filteredFoods = this.applyCategoryFilter(sourceFoods, this.activeCategory);
            const visibleFoods = this.nearMeState.active
                ? this.applyDistanceSorting(filteredFoods)
                : filteredFoods.map((food) => ({ ...food, distance_km: null }));

            this.visibleFoods = visibleFoods;
            this.renderCards(visibleFoods);
            this.syncMap(visibleFoods);
        }

        applyDistanceSorting(foods) {
            const { lat, lng } = this.nearMeState;

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return Array.isArray(foods) ? foods : [];
            }

            return (Array.isArray(foods) ? foods : [])
                .map((food) => ({
                    ...food,
                    distance_km: this.calculateDistanceKm(lat, lng, Number(food.latitude), Number(food.longitude)),
                }))
                .sort((left, right) => {
                    const leftDistance = Number.isFinite(left.distance_km) ? left.distance_km : Number.MAX_SAFE_INTEGER;
                    const rightDistance = Number.isFinite(right.distance_km) ? right.distance_km : Number.MAX_SAFE_INTEGER;

                    return leftDistance - rightDistance;
                });
        }

        renderCards(foods) {
            const list = Array.isArray(foods) ? foods : [];

            this.grid.innerHTML = list.map((food) => this.renderCardMarkup(food)).join('');
            this.toggleNoResults(list.length === 0);
            this.updateResultsCount(list.length);
            this.publishVisibleFoods(list);
            window.I18n?.applyTranslations(this.grid);
        }

        renderCardMarkup(food) {
            const rating = Number(food.rating || 0);
            const filledStars = Math.max(0, Math.min(5, Math.round(rating)));
            const stars = `${'★'.repeat(filledStars)}${'☆'.repeat(5 - filledStars)}`;
            const isFavorited = window.FavoriteStore?.isFavorited(food.id, Boolean(food.is_favorited)) || Boolean(food.is_favorited);
            const favoriteTitleKey = isFavorited ? 'actions.removeFromFavorites' : 'actions.addToFavorites';
            const favoriteTitle = window.I18n?.t(favoriteTitleKey) || (isFavorited ? 'Remove from Favorites' : 'Add to Favorites');
            const favoriteMarkup = `
                    <button
                        type="button"
                        class="food-card__favorite${isFavorited ? ' is-active' : ''}"
                        data-favorite-toggle
                        data-food-id="${this.escapeAttribute(food.id)}"
                        data-favorited="${isFavorited ? 'true' : 'false'}"
                        data-i18n-title="${favoriteTitleKey}"
                        data-i18n-aria-label="${favoriteTitleKey}"
                        title="${this.escapeAttribute(favoriteTitle)}"
                        aria-label="${this.escapeAttribute(favoriteTitle)}"
                    >${isFavorited ? '♥' : '♡'}</button>
                `;
            const distanceMarkup = Number.isFinite(Number(food.distance_km))
                ? `<span class="food-card__distance is-visible" data-distance-badge>${this.escapeHtml(window.I18n?.formatDistance(Number(food.distance_km)) || `${Number(food.distance_km).toFixed(1)} km`)}</span>`
                : '<span class="food-card__distance" data-distance-badge></span>';

            return `
                <article
                    class="food-card${Number.isFinite(Number(food.distance_km)) ? ' is-near-me' : ''}"
                    data-food-card
                    data-food-id="${this.escapeAttribute(food.id)}"
                    data-category-slug="${this.escapeAttribute(food.category_slug || '')}"
                    data-detail-url="/food/${this.escapeAttribute(food.id)}"
                    data-favorited="${isFavorited ? 'true' : 'false'}"
                >
                    <img class="food-card__image" src="${this.escapeAttribute(food.image_url || '')}" alt="${this.escapeAttribute(food.name || '')}" loading="lazy" decoding="async">
                    <div class="food-card__body">
                        <div class="food-card__topline">
                            <span class="food-card__badge">${this.escapeHtml(`${food.category_icon || ''} ${food.category_name || ''}`.trim())}</span>
                            <div class="food-card__actions">
                                ${distanceMarkup}
                                <span class="food-card__rating" aria-label="Rating ${this.escapeAttribute(rating.toFixed(1))} out of 5">${this.escapeHtml(stars)}</span>
                                ${favoriteMarkup}
                            </div>
                        </div>
                        <h3 class="food-card__title">${this.escapeHtml(food.name || '')}</h3>
                        <p class="food-card__description">${this.escapeHtml(food.description || '')}</p>
                        <div class="food-card__meta">
                            <span class="food-card__meta-item">📍 ${this.escapeHtml(food.area || '')}</span>
                            <span class="food-card__meta-item">💰 ${this.escapeHtml(food.price_range || '')}</span>
                            <span class="food-card__meta-item">⭐ ${this.escapeHtml(rating.toFixed(1))}</span>
                        </div>
                    </div>
                </article>
            `;
        }

        applyCategoryFilter(foods, slug) {
            if (!Array.isArray(foods) || slug === 'all') {
                return Array.isArray(foods) ? foods : [];
            }

            return foods.filter((food) => food.category_slug === slug);
        }

        getVisibleCardsCount() {
            return Array.isArray(this.visibleFoods) ? this.visibleFoods.length : 0;
        }

        getVisibleFoods() {
            return Array.isArray(this.visibleFoods) ? [...this.visibleFoods] : [];
        }

        publishVisibleFoods(foods) {
            window.__VISIBLE_FOODS__ = Array.isArray(foods) ? foods : [];
        }

        syncMap(foods) {
            const visibleFoods = Array.isArray(foods) ? foods : [];

            this.publishVisibleFoods(visibleFoods);

            if (window.macauMap) {
                window.macauMap.updateMarkers(visibleFoods);
            }

            document.dispatchEvent(new CustomEvent('catalog:visible-foods-change', {
                detail: { foods: visibleFoods },
            }));
        }

        syncActivePill() {
            this.root.querySelectorAll('[data-category-pill]').forEach((pill) => {
                const isActive = (pill.dataset.categorySlug || 'all') === this.activeCategory;
                pill.classList.toggle('is-active', isActive);
            });
        }

        toggleNoResults(show) {
            if (!this.noResultsElement) {
                return;
            }

            this.noResultsElement.classList.toggle('is-visible', Boolean(show));
        }

        updateResultsCount(count) {
            if (this.countElement) {
                this.countElement.textContent = window.I18n?.formatDishesCount(count) || `${count} dish${count === 1 ? '' : 'es'}`;
            }
        }

        updateFavoriteState(foodId, isFavorited) {
            const foodKey = String(foodId);
            const apply = (food) => (String(food.id) === foodKey ? { ...food, is_favorited: isFavorited } : food);

            this.allFoods = this.allFoods.map(apply);
            this.currentFoods = this.currentFoods.map(apply);
            this.visibleFoods = this.visibleFoods.map(apply);

            const safeSelector = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(foodKey)
                : foodKey.replace(/"/g, '\\"');
            const card = this.grid.querySelector(`[data-food-card][data-food-id="${safeSelector}"]`);

            if (!card) {
                return;
            }

            card.dataset.favorited = isFavorited ? 'true' : 'false';
            const button = card.querySelector('[data-favorite-toggle]');

            if (!button) {
                return;
            }

            const titleKey = isFavorited ? 'actions.removeFromFavorites' : 'actions.addToFavorites';
            const title = window.I18n?.t(titleKey) || (isFavorited ? 'Remove from Favorites' : 'Add to Favorites');

            button.dataset.favorited = isFavorited ? 'true' : 'false';
            button.classList.toggle('is-active', isFavorited);
            button.textContent = isFavorited ? '♥' : '♡';
            button.setAttribute('data-i18n-title', titleKey);
            button.setAttribute('data-i18n-aria-label', titleKey);
            button.setAttribute('title', title);
            button.setAttribute('aria-label', title);
        }

        animateFavoriteButton(button, isFavorited) {
            button.classList.toggle('is-active', isFavorited);
            button.classList.remove('is-bouncing');
            void button.offsetWidth;
            button.classList.add('is-bouncing');

            window.setTimeout(() => {
                button.classList.remove('is-bouncing');
            }, 320);
        }

        calculateDistanceKm(lat1, lng1, lat2, lng2) {
            if (![lat1, lng1, lat2, lng2].every((value) => Number.isFinite(value))) {
                return Number.NaN;
            }

            const toRadians = (value) => (value * Math.PI) / 180;
            const earthRadiusKm = 6371;
            const deltaLat = toRadians(lat2 - lat1);
            const deltaLng = toRadians(lng2 - lng1);
            const a = Math.sin(deltaLat / 2) ** 2
                + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(deltaLng / 2) ** 2;

            return 2 * earthRadiusKm * Math.asin(Math.sqrt(a));
        }

        escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        escapeAttribute(value) {
            return this.escapeHtml(value);
        }
    }

    window.FoodCatalog = {
        init(options) {
            return FoodCatalog.init(options);
        },
    };
})();
