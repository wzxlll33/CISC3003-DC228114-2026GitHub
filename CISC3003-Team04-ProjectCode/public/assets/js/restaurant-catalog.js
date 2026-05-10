(() => {
    class RestaurantCatalog {
        constructor(options = {}) {
            this.root = options.root;
            this.cardsContainer = this.root?.querySelector('[data-restaurant-cards]');
            this.topRatedContainer = this.root?.querySelector('[data-top-rated-list]');
            this.countElements = Array.from(this.root?.querySelectorAll('[data-results-count]') || []);
            this.noResultsElement = this.root?.querySelector('[data-no-results]');
            this.errorElement = this.root?.querySelector('[data-search-error]');
            this.searchInput = this.root?.querySelector('#restaurant-search');
            this.searchShell = this.root?.querySelector('[data-search-shell]');
            this.suggestionsPanel = this.root?.querySelector('[data-search-suggestions]');
            this.toolsMenu = this.root?.querySelector('[data-explore-tools]');
            this.resultsPanel = this.root?.querySelector('[data-results-panel]');
            this.filterPanel = this.root?.querySelector('[data-filter-panel]');
            this.mustEatPanel = this.root?.querySelector('[data-must-eat-panel]');
            this.backdrop = this.root?.querySelector('[data-panel-backdrop]');
            this.activeSummary = this.root?.querySelector('[data-active-filter-summary]');
            this.toggleToolsButtons = Array.from(this.root?.querySelectorAll('[data-toggle-tools]') || []);
            this.openResultsButtons = Array.from(this.root?.querySelectorAll('[data-open-results]') || []);
            this.openFiltersButtons = Array.from(this.root?.querySelectorAll('[data-open-filters]') || []);
            this.openMustEatButtons = Array.from(this.root?.querySelectorAll('[data-open-must-eat]') || []);
            this.openLocalFavoritesButtons = Array.from(this.root?.querySelectorAll('[data-open-local-favorites]') || []);
            this.closeResultsButtons = Array.from(this.root?.querySelectorAll('[data-close-results]') || []);
            this.searchToggleButtons = Array.from(document.querySelectorAll('[data-explore-search-toggle]') || []);
            this.panelDrag = null;
            this.searchVisible = true;
            this.activeCategory = 'all';
            this.activeServiceFilter = 'all';
            this.draftCategory = 'all';
            this.draftServiceFilter = 'all';
            this.searchTerm = '';
            this.locationSearch = null;
            this.showFavoritesOnLoad = false;
            this.debounceTimer = null;
            this.lastLoggedSearchKey = '';
            this.searchShellResizeObserver = null;
            this.searchShellMetricsFrame = null;
            this.allRestaurants = Array.isArray(options.restaurants) ? options.restaurants : [];
            this.nearbyRestaurants = [];
            this.topRated = Array.isArray(options.topRated) ? options.topRated : [];
            this.foods = Array.isArray(options.foods) ? options.foods : [];
            this.visibleRestaurants = [...this.allRestaurants];
            this.categoryIndex = this.buildCategoryIndex(this.foods);
            this.labels = {
                reviews: 'reviews',
                viewDetails: 'View details',
                openTools: 'Open explore tools',
                closeTools: 'Close explore tools',
                allRestaurants: 'All restaurants',
                mustEat: 'Must-eat',
                highRated: 'High rated',
                nearby: 'Nearby',
                favorites: 'Favorites',
                nearbyRestaurants: 'Nearby restaurants',
                nearbyFood: 'Food near me',
                mapLocation: 'Search map location',
                useLocation: 'Use my location',
                showAll: 'Show all restaurants',
                locationUnavailable: 'Location is unavailable in this browser. Showing all restaurants instead.',
                locationDenied: 'Unable to use your location. Please allow location access and try again.',
                locationSearching: 'Finding nearby restaurants...',
                showingNearby: 'Showing restaurants near your location.',
                locationNotFound: 'No map location was found. Try another place or area name.',
                locationSearchError: 'Map location search is unavailable right now. Please try again later.',
                reportIssue: 'Report issue',
                spotsSuffix: 'spots',
                ...(options.labels || {}),
            };

            if (!this.root || !this.cardsContainer) {
                return;
            }

            this.bindSearch();
            this.bindCards();
            this.bindTopRated();
            this.bindPanels();
            this.bindMobileSheet();
            this.bindSearchVisibility();
            this.bindFilterPanel();
            this.bindSearchShellMetrics();
            this.applyInitialStateFromUrl();
            this.renderTopRated(this.topRated);
            this.applyFilters({ openResults: this.searchTerm.length > 0 });
            if (this.showFavoritesOnLoad) {
                this.showLocalFavorites({ focusFirstCard: false });
            } else {
                this.syncAllUi();
                this.setResultsSheetState('peek');
            }
            this.openResultsButtons.forEach((button) => button.setAttribute('aria-expanded', this.resultsPanel?.hidden ? 'false' : 'true'));
        }

        static init(options = {}) {
            return new RestaurantCatalog(options);
        }

        buildCategoryIndex(foods) {
            return (Array.isArray(foods) ? foods : []).reduce((lookup, food) => {
                const slug = String(food.category_slug || '').trim();
                const restaurantIds = Array.isArray(food.restaurant_ids) ? food.restaurant_ids : [];

                if (!slug) {
                    return lookup;
                }

                lookup[slug] = lookup[slug] || new Set();
                restaurantIds.forEach((restaurantId) => {
                    lookup[slug].add(String(restaurantId));
                });

                return lookup;
            }, {});
        }

        bindSearch() {
            if (!this.searchInput) {
                return;
            }

            this.searchInput.addEventListener('focus', () => {
                this.closeTools();
                this.setSuggestionsOpen(true);
            });

            this.searchInput.addEventListener('input', () => {
                this.setSuggestionsOpen(true);
                window.clearTimeout(this.debounceTimer);
                this.debounceTimer = window.setTimeout(() => {
                    if (this.isLocationQuery(this.searchInput.value || '')) {
                        return;
                    }

                    this.search(this.searchInput.value || '', {
                        openResults: true,
                        preserveFocus: true,
                        commitHistory: false,
                    });
                }, 220);
            });

            this.searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(this.debounceTimer);

                    if (this.isLocationQuery(this.searchInput.value || '')) {
                        this.activateNearbySearch({
                            label: this.searchInput.value || this.labels.nearbyRestaurants,
                            commitHistory: true,
                        });
                        return;
                    }

                    this.search(this.searchInput.value || '', {
                        openResults: true,
                        preserveFocus: true,
                        commitHistory: true,
                        allowLocationFallback: true,
                    });
                }
            });

            this.suggestionsPanel?.addEventListener('pointerdown', (event) => {
                event.preventDefault();
            });

            this.suggestionsPanel?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-search-suggestion]');

                if (!button) {
                    return;
                }

                const suggestion = button.dataset.searchSuggestion || 'map-location';

                if (suggestion === 'use-location') {
                    this.activateNearbySearch({
                        label: this.labels.useLocation,
                        commitHistory: true,
                    });
                    return;
                }

                if (suggestion === 'show-all') {
                    this.showAllRestaurants();
                    return;
                }

                this.searchMapLocation(this.searchInput.value || button.textContent.trim(), {
                    commitHistory: true,
                    preserveFocus: true,
                });
            });

            this.searchInput.addEventListener('blur', () => {
                window.setTimeout(() => this.setSuggestionsOpen(false), 120);
            });
        }

        bindSearchShellMetrics() {
            if (!this.searchShell || !this.root) {
                return;
            }

            const sync = () => this.queueSearchShellMetrics();

            if ('ResizeObserver' in window) {
                this.searchShellResizeObserver = new ResizeObserver(sync);
                this.searchShellResizeObserver.observe(this.searchShell);
            }

            window.addEventListener('resize', sync);
            this.queueSearchShellMetrics();
        }

        queueSearchShellMetrics() {
            if (!this.searchShell || !this.root) {
                return;
            }

            window.cancelAnimationFrame?.(this.searchShellMetricsFrame);
            this.searchShellMetricsFrame = window.requestAnimationFrame(() => {
                const height = Math.ceil(this.searchShell.getBoundingClientRect().height || 0);
                this.root.style.setProperty('--explore-search-shell-height', `${height}px`);
            });
        }

        bindCards() {
            this.cardsContainer.addEventListener('click', (event) => {
                const detailLink = event.target.closest('[data-detail-link]');

                if (detailLink) {
                    return;
                }

                if (event.target.closest('[data-feedback-open]')) {
                    return;
                }

                const card = event.target.closest('[data-restaurant-card]');

                if (!card) {
                    return;
                }

                const restaurantId = card.dataset.restaurantId;

                if (restaurantId && window.macauMap) {
                    if (typeof window.macauMap.selectRestaurant === 'function') {
                        window.macauMap.selectRestaurant(restaurantId);
                    } else {
                        window.macauMap.flyToRestaurant(restaurantId);
                    }
                }
            });

            this.cardsContainer.addEventListener('mouseover', (event) => {
                const card = event.target.closest('[data-restaurant-card]');

                if (!card?.dataset.restaurantId || !window.macauMap) {
                    return;
                }

                window.macauMap.highlightRestaurantMarker(card.dataset.restaurantId, 900);
            });
        }

        bindTopRated() {
            if (!this.topRatedContainer) {
                return;
            }

            this.topRatedContainer.addEventListener('click', (event) => {
                const item = event.target.closest('[data-top-rated-id]');

                if (!item) {
                    return;
                }

                const restaurantId = item.dataset.topRatedId;

                if (restaurantId && window.macauMap) {
                    if (typeof window.macauMap.selectRestaurant === 'function') {
                        window.macauMap.selectRestaurant(restaurantId);
                    } else {
                        window.macauMap.flyToRestaurant(restaurantId);
                    }
                    this.openResults();
                    window.setTimeout(() => {
                        this.highlightCard(restaurantId);
                    }, 650);
                }
            });
        }

        bindPanels() {
            this.toggleToolsButtons.forEach((button) => {
                button.addEventListener('click', () => this.toggleTools());
            });

            this.openResultsButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.closeTools();
                    this.openResults({ focusFirstCard: true });
                });
            });

            this.openFiltersButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.closeTools();
                    this.openFilters();
                });
            });

            this.openMustEatButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.closeTools();
                    this.openMustEat();
                });
            });

            this.openLocalFavoritesButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.closeTools();
                    this.showLocalFavorites();
                });
            });

            this.closeResultsButtons.forEach((button) => {
                button.addEventListener('click', () => this.closeResults());
            });

            this.root.querySelectorAll('[data-close-filters]').forEach((button) => {
                button.addEventListener('click', () => this.closeFilters());
            });

            this.root.querySelectorAll('[data-close-must-eat]').forEach((button) => {
                button.addEventListener('click', () => this.closeMustEat());
            });

            this.backdrop?.addEventListener('click', () => {
                this.closeFilters();
                this.closeMustEat();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                this.closeTools();
                this.closeFilters();
                this.closeMustEat();
            });
        }

        bindFilterPanel() {
            this.root.querySelectorAll('[data-filter-service]').forEach((button) => {
                button.addEventListener('click', () => {
                    this.draftServiceFilter = button.dataset.filterService || 'all';
                    this.syncDraftFilters();
                });
            });

            this.root.querySelectorAll('[data-filter-category]').forEach((button) => {
                button.addEventListener('click', () => {
                    this.draftCategory = button.dataset.filterCategory || 'all';
                    this.syncDraftFilters();
                });
            });

            this.root.querySelectorAll('[data-apply-filters]').forEach((button) => {
                button.addEventListener('click', () => {
                    this.applyDraftFilters();
                });
            });

            this.root.querySelectorAll('[data-reset-filters]').forEach((button) => {
                button.addEventListener('click', () => {
                    this.draftCategory = 'all';
                    this.draftServiceFilter = 'all';
                    this.syncDraftFilters();
                    this.applyDraftFilters();
                });
            });
        }

        applyInitialStateFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const query = (params.get('q') || '').trim();
            const category = (params.get('category') || '').trim();
            const service = (params.get('service') || '').trim();
            const allowedServices = new Set(['all', 'must-eat', 'high-rated', 'nearby']);
            this.showFavoritesOnLoad = params.get('favorites') === '1';

            if (category) {
                this.activeCategory = category;
                this.draftCategory = category;
            }

            if (service && allowedServices.has(service)) {
                this.activeServiceFilter = service;
                this.draftServiceFilter = service;
            }

            if (!query) {
                return;
            }

            this.searchTerm = query;

            if (this.searchInput) {
                this.searchInput.value = query;
            }
        }

        openResults(options = {}) {
            this.closeTools();
            this.setHidden(this.resultsPanel, false);
            this.setResultsSheetState(options.expanded ? 'expanded' : 'peek');
            this.openResultsButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
            this.openLocalFavoritesButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));

            if (options.focusFirstCard) {
                window.setTimeout(() => this.cardsContainer?.querySelector('[data-restaurant-card]')?.focus?.(), 50);
            }
        }

        closeResults() {
            if (this.isMobileViewport()) {
                const isCollapsed = this.resultsPanel?.classList.contains('is-collapsed');
                this.setHidden(this.resultsPanel, false);
                this.setResultsSheetState(isCollapsed ? 'peek' : 'collapsed');
                this.openResultsButtons.forEach((button) => button.setAttribute('aria-expanded', isCollapsed ? 'true' : 'false'));
                this.openLocalFavoritesButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
                return;
            }

            this.setHidden(this.resultsPanel, true);
            this.openResultsButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
            this.openLocalFavoritesButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
        }

        bindMobileSheet() {
            if (!this.resultsPanel) {
                return;
            }

            const header = this.resultsPanel.querySelector('.explore-panel-header');
            const setStateFromDelta = (deltaY) => {
                if (deltaY > 38) {
                    this.setResultsSheetState('collapsed');
                    return;
                }

                if (deltaY < -38) {
                    this.setResultsSheetState('expanded');
                    return;
                }

                this.setResultsSheetState('peek');
            };

            header?.addEventListener('click', (event) => {
                if (event.target.closest('button, a')) {
                    return;
                }

                const isExpanded = this.resultsPanel.classList.contains('is-expanded');
                const isCollapsed = this.resultsPanel.classList.contains('is-collapsed');
                this.setResultsSheetState(isExpanded ? 'peek' : (isCollapsed ? 'peek' : 'expanded'));
            });

            header?.addEventListener('pointerdown', (event) => {
                if (!this.isMobileViewport() || event.target.closest('button, a, input, textarea, select')) {
                    return;
                }

                this.panelDrag = {
                    pointerId: event.pointerId,
                    startY: event.clientY,
                };
                header.setPointerCapture?.(event.pointerId);
            });

            header?.addEventListener('pointerup', (event) => {
                if (!this.panelDrag || this.panelDrag.pointerId !== event.pointerId) {
                    return;
                }

                setStateFromDelta(event.clientY - this.panelDrag.startY);
                this.panelDrag = null;
            });

            header?.addEventListener('pointercancel', () => {
                this.panelDrag = null;
            });
        }

        setResultsSheetState(state) {
            if (!this.resultsPanel) {
                return;
            }

            const resolvedState = ['collapsed', 'peek', 'expanded'].includes(state) ? state : 'peek';
            this.resultsPanel.dataset.sheetState = resolvedState;
            this.resultsPanel.classList.toggle('is-collapsed', resolvedState === 'collapsed');
            this.resultsPanel.classList.toggle('is-expanded', resolvedState === 'expanded');
            this.resultsPanel.classList.toggle('is-peek', resolvedState === 'peek');
            this.closeResultsButtons.forEach((button) => {
                button.setAttribute('aria-expanded', resolvedState === 'collapsed' ? 'false' : 'true');
            });
        }

        bindSearchVisibility() {
            if (!this.searchShell || this.searchToggleButtons.length === 0) {
                return;
            }

            this.searchToggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.setSearchVisible(!this.searchVisible, { focus: true });
                });
            });

            this.setSearchVisible(true);
        }

        setSearchVisible(isVisible, options = {}) {
            this.searchVisible = Boolean(isVisible);
            this.root?.classList.toggle('is-search-hidden', !this.searchVisible);
            this.searchToggleButtons.forEach((button) => {
                button.setAttribute('aria-expanded', this.searchVisible ? 'true' : 'false');
                button.classList.toggle('is-active', this.searchVisible);
            });

            if (!this.searchVisible) {
                this.closeTools();
                this.setSuggestionsOpen(false);
                return;
            }

            this.queueSearchShellMetrics();
            if (options.focus) {
                window.setTimeout(() => this.searchInput?.focus?.(), 180);
            }
        }

        isMobileViewport() {
            return window.matchMedia('(max-width: 640px)').matches;
        }

        openFilters() {
            this.closeTools();
            this.draftCategory = this.activeCategory;
            this.draftServiceFilter = this.activeServiceFilter;
            this.syncDraftFilters();
            this.setHidden(this.filterPanel, false);
            this.setHidden(this.backdrop, false);
            this.openFiltersButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
            this.filterPanel?.querySelector('[data-filter-service]')?.focus?.();
        }

        closeFilters() {
            this.setHidden(this.filterPanel, true);
            this.openFiltersButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
            this.syncBackdrop();
        }

        openMustEat() {
            this.closeTools();
            this.setHidden(this.mustEatPanel, false);
            this.setHidden(this.backdrop, false);
            this.openMustEatButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
            this.mustEatPanel?.querySelector('[data-top-rated-id]')?.focus?.();
        }

        showLocalFavorites(options = {}) {
            const favoriteIds = new Set((window.FavoriteStore?.getFavoriteIds?.() || []).map((id) => String(id)));
            const restaurantIds = new Set();

            this.foods.forEach((food) => {
                if (!favoriteIds.has(String(food.id))) {
                    return;
                }

                (Array.isArray(food.restaurant_ids) ? food.restaurant_ids : []).forEach((restaurantId) => {
                    restaurantIds.add(String(restaurantId));
                });
            });

            const restaurants = this.allRestaurants.filter((restaurant) => restaurantIds.has(String(restaurant.id)));
            this.searchTerm = '';

            if (this.searchInput) {
                this.searchInput.value = this.labels.favorites;
            }

            this.renderCards(restaurants);
            this.syncAllUi();
            if (this.activeSummary) {
                this.activeSummary.textContent = `${this.labels.favorites} · ${restaurants.length} ${this.labels.spotsSuffix}`;
            }
            this.openResults({ focusFirstCard: options.focusFirstCard !== false });
            this.openLocalFavoritesButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
        }

        closeMustEat() {
            this.setHidden(this.mustEatPanel, true);
            this.openMustEatButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
            this.syncBackdrop();
        }

        syncBackdrop() {
            const overlayOpen = !this.filterPanel?.hidden || !this.mustEatPanel?.hidden;
            this.setHidden(this.backdrop, !overlayOpen);
        }

        setHidden(element, hidden) {
            if (!element) {
                return;
            }

            element.hidden = hidden;
        }

        toggleTools() {
            this.setToolsOpen(Boolean(this.toolsMenu?.hidden));
        }

        closeTools() {
            this.setToolsOpen(false);
        }

        setToolsOpen(isOpen) {
            this.setHidden(this.toolsMenu, !isOpen);
            this.searchShell?.classList.toggle('is-tools-open', isOpen);
            if (isOpen) {
                this.setSuggestionsOpen(false);
            }
            this.toggleToolsButtons.forEach((button) => {
                button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                button.setAttribute('aria-label', isOpen ? this.labels.closeTools : this.labels.openTools);
            });
            this.queueSearchShellMetrics();
        }

        setSuggestionsOpen(isOpen) {
            if (!this.suggestionsPanel) {
                return;
            }

            this.setHidden(this.suggestionsPanel, !isOpen);
            this.queueSearchShellMetrics();
        }

        async applyDraftFilters() {
            this.activeCategory = this.draftCategory || 'all';
            this.activeServiceFilter = this.draftServiceFilter || 'all';

            if (this.activeServiceFilter !== 'nearby') {
                this.locationSearch = null;
                window.macauMap?.removeSearchLocation?.();
                window.macauMap?.removeUserLocation?.();
            }

            let nearbyLoaded = true;
            if (this.activeServiceFilter === 'nearby') {
                nearbyLoaded = await this.loadNearbyRestaurants();
            }

            await this.applyFilters({ openResults: true, preserveError: !nearbyLoaded });
            this.closeFilters();
        }

        async activateNearbySearch(options = {}) {
            this.closeTools();
            this.setSuggestionsOpen(false);
            this.locationSearch = null;
            window.macauMap?.removeSearchLocation?.();
            this.activeServiceFilter = 'nearby';
            this.draftServiceFilter = 'nearby';
            this.draftCategory = this.activeCategory;
            this.searchTerm = '';

            if (this.searchInput) {
                this.searchInput.value = options.label || this.labels.nearbyRestaurants;
            }

            this.showError(this.labels.locationSearching);
            const nearbyLoaded = await this.loadNearbyRestaurants({ forceRefresh: true });
            await this.applyFilters({
                openResults: true,
                preserveFocus: true,
                commitHistory: Boolean(options.commitHistory),
                preserveError: !nearbyLoaded,
            });
            if (nearbyLoaded) {
                this.clearError();
            }
            this.syncDraftFilters();
        }

        async loadNearbyRestaurants(options = {}) {
            if (this.nearbyRestaurants.length > 0 && !options.forceRefresh) {
                return true;
            }

            if (!navigator.geolocation || !window.api) {
                this.showError(this.labels.locationUnavailable);
                this.activeServiceFilter = 'all';
                this.draftServiceFilter = 'all';
                this.openResults();
                return false;
            }

            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 8000,
                        maximumAge: 60000,
                    });
                });
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const payload = await window.api.get(`/api/restaurants/nearby?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&radius=4`);
                this.nearbyRestaurants = Array.isArray(payload) ? payload : [];

                if (window.macauMap) {
                    window.macauMap.showUserLocation(lat, lng);
                }
                return true;
            } catch (error) {
                this.showError(this.labels.locationDenied);
                this.activeServiceFilter = 'all';
                this.draftServiceFilter = 'all';
                this.openResults();
                return false;
            }
        }

        async searchMapLocation(query, options = {}) {
            const normalizedQuery = String(query || '').trim();

            if (!normalizedQuery || !window.api) {
                this.showError(this.labels.locationNotFound);
                this.openResults();
                return false;
            }

            this.closeTools();
            this.setSuggestionsOpen(false);
            this.showError(this.labels.locationSearching);

            try {
                const geocodePayload = await window.api.get(`/api/map/geocode?q=${encodeURIComponent(normalizedQuery)}`);
                const location = Array.isArray(geocodePayload?.results) ? geocodePayload.results[0] : null;

                if (!location) {
                    this.renderCards([]);
                    this.syncAllUi();
                    this.showError(this.labels.locationNotFound);
                    this.openResults();
                    return false;
                }

                const lat = Number(location.latitude);
                const lng = Number(location.longitude);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    this.renderCards([]);
                    this.syncAllUi();
                    this.showError(this.labels.locationNotFound);
                    this.openResults();
                    return false;
                }

                const label = location.name || location.display_name || normalizedQuery;
                const restaurants = await this.loadRestaurantsNearCoordinates(lat, lng);
                this.nearbyRestaurants = restaurants;
                this.locationSearch = { lat, lng, label };
                this.activeServiceFilter = 'nearby';
                this.draftServiceFilter = 'nearby';
                this.draftCategory = this.activeCategory;
                this.searchTerm = '';

                if (this.searchInput) {
                    this.searchInput.value = label;
                }

                window.macauMap?.showSearchLocation?.(lat, lng, label);
                await this.applyFilters({
                    openResults: true,
                    preserveFocus: Boolean(options.preserveFocus),
                });
                this.clearError();

                if (options.commitHistory) {
                    this.logSearchHistory(normalizedQuery, restaurants.length);
                }

                return true;
            } catch (error) {
                this.renderCards([]);
                this.syncAllUi();
                this.showError(this.labels.locationSearchError);
                this.openResults();
                return false;
            }
        }

        async loadRestaurantsNearCoordinates(lat, lng) {
            if (!window.api || !Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) {
                return [];
            }

            const payload = await window.api.get(`/api/restaurants/nearby?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&radius=4`);
            return Array.isArray(payload) ? payload : [];
        }

        async showAllRestaurants() {
            this.closeTools();
            this.setSuggestionsOpen(false);
            this.locationSearch = null;
            this.searchTerm = '';
            this.activeCategory = 'all';
            this.activeServiceFilter = 'all';
            this.draftCategory = 'all';
            this.draftServiceFilter = 'all';
            window.macauMap?.removeSearchLocation?.();
            window.macauMap?.removeUserLocation?.();

            if (this.searchInput) {
                this.searchInput.value = '';
            }

            await this.applyFilters({ openResults: true });
        }

        async search(query, options = {}) {
            this.searchTerm = String(query || '').trim();
            if (!options.keepLocation) {
                this.locationSearch = null;
                window.macauMap?.removeSearchLocation?.();
            }
            await this.applyFilters({
                openResults: options.openResults || this.searchTerm.length > 0,
                preserveFocus: Boolean(options.preserveFocus),
                commitHistory: Boolean(options.commitHistory),
                allowLocationFallback: Boolean(options.allowLocationFallback),
            });
        }

        async applyFilters(options = {}) {
            if (!options.preserveError) {
                this.clearError();
            }

            const sourceRestaurants = this.activeServiceFilter === 'nearby' && this.nearbyRestaurants.length > 0
                ? this.nearbyRestaurants
                : this.allRestaurants;
            const categoryFiltered = this.filterRestaurantsByActiveCategory(sourceRestaurants);
            const serviceFiltered = this.filterRestaurantsByActiveService(categoryFiltered);

            if (!this.searchTerm) {
                this.renderCards(serviceFiltered);
                this.syncAllUi();

                if (options.openResults) {
                    this.openResults();
                }

                return;
            }

            const localMatches = serviceFiltered.filter((restaurant) => this.matchesQuery(restaurant, this.searchTerm));
            const shouldCommitHistory = Boolean(options.commitHistory);

            if (localMatches.length > 0) {
                this.renderCards(localMatches);
                this.syncAllUi();
                if (shouldCommitHistory) {
                    this.logSearchHistory(this.searchTerm, localMatches.length);
                }
                this.openResults();
                return;
            }

            if (!window.api) {
                this.renderCards([]);
                this.syncAllUi();
                this.openResults();
                return;
            }

            try {
                const response = await window.api.get(`/api/restaurants/search?q=${encodeURIComponent(this.searchTerm)}`);
                const remoteMatches = this.filterRestaurantsByActiveService(
                    this.filterRestaurantsByActiveCategory(Array.isArray(response) ? response : [])
                );
                if (remoteMatches.length === 0 && options.allowLocationFallback) {
                    await this.searchMapLocation(this.searchTerm, {
                        preserveFocus: Boolean(options.preserveFocus),
                        commitHistory: shouldCommitHistory,
                    });
                    return;
                }
                this.renderCards(remoteMatches);
                if (shouldCommitHistory) {
                    this.logSearchHistory(this.searchTerm, remoteMatches.length);
                }
            } catch (error) {
                this.showError(error?.message || 'Unable to search restaurants right now.');
                this.renderCards([]);
            }

            this.syncAllUi();
            this.openResults();
        }

        async logSearchHistory(query, resultsCount) {
            const normalizedQuery = String(query || '').trim();

            if (!normalizedQuery || !window.api || window.__APP_CONTEXT__?.isLoggedIn === false) {
                return;
            }

            const filters = this.activeHistoryFilters();
            const searchKey = JSON.stringify({
                query: normalizedQuery,
                filters,
            });

            if (searchKey === this.lastLoggedSearchKey) {
                return;
            }

            this.lastLoggedSearchKey = searchKey;

            try {
                await window.api.post('/api/history/search', {
                    query: normalizedQuery,
                    filters,
                    results_count: Number.isFinite(Number(resultsCount)) ? Number(resultsCount) : 0,
                });
            } catch (error) {
                console.error(error);
            }
        }

        activeHistoryFilters() {
            const filters = {};

            if (this.activeCategory && this.activeCategory !== 'all') {
                filters.category = this.activeCategory;
            }

            if (this.activeServiceFilter && this.activeServiceFilter !== 'all') {
                filters.service = this.activeServiceFilter;
            }

            return filters;
        }

        matchesQuery(restaurant, query) {
            const haystack = [restaurant.name, restaurant.description, restaurant.area]
                .map((value) => String(value || '').toLowerCase())
                .join(' ');

            return haystack.includes(String(query).toLowerCase());
        }

        isLocationQuery(query) {
            const normalized = String(query || '').trim().toLowerCase();

            if (!normalized) {
                return false;
            }

            const compact = normalized.replace(/\s+/g, '');
            const keywords = [
                '附近',
                '附近餐廳',
                '附近的餐廳',
                '附近美食',
                '我附近的美食',
                'nearby',
                'nearbyrestaurants',
                'nearbyfood',
                'foodnearme',
                'restaurantsnearme',
                'near me',
                'perto',
                'perto de mim',
                'comida perto',
                'restaurantes perto',
            ];

            return keywords.some((keyword) => compact.includes(keyword.replace(/\s+/g, '')));
        }

        filterRestaurantsByActiveCategory(restaurants) {
            if (this.activeCategory === 'all') {
                return Array.isArray(restaurants) ? restaurants : [];
            }

            const allowed = this.categoryIndex[this.activeCategory];

            if (!(allowed instanceof Set)) {
                return [];
            }

            return (Array.isArray(restaurants) ? restaurants : []).filter((restaurant) => allowed.has(String(restaurant.id)));
        }

        filterRestaurantsByActiveService(restaurants) {
            const list = Array.isArray(restaurants) ? restaurants : [];

            if (this.activeServiceFilter === 'high-rated') {
                return list.filter((restaurant) => Number(restaurant.avg_rating || 0) >= 4);
            }

            if (this.activeServiceFilter === 'must-eat') {
                const mustEatTags = new Set(['must-try', 'local-favorite', 'award-winning', 'queue-worthy', 'heritage']);

                return list.filter((restaurant) => (
                    Number(restaurant.avg_rating || 0) >= 4
                    || (Array.isArray(restaurant.tags) && restaurant.tags.some((tag) => mustEatTags.has(String(tag.slug || ''))))
                ));
            }

            return list;
        }

        renderCards(restaurants) {
            const list = Array.isArray(restaurants) ? restaurants : [];

            this.cardsContainer.innerHTML = list.map((restaurant, index) => this.renderCardMarkup(restaurant, index)).join('');
            this.visibleRestaurants = list;

            this.countElements.forEach((element) => {
                element.textContent = String(list.length);
            });

            if (this.noResultsElement) {
                this.noResultsElement.hidden = list.length !== 0;
            }

            if (window.macauMap) {
                window.macauMap.updateRestaurantMarkers(list);
            }
        }

        renderTopRated(topRated) {
            if (!this.topRatedContainer) {
                return;
            }

            const list = Array.isArray(topRated) ? topRated.slice(0, 8) : [];

            this.topRatedContainer.innerHTML = list.map((restaurant, index) => `
                <button type="button" class="must-eat__item" data-top-rated-id="${this.escapeAttribute(restaurant.id)}">
                    <span class="must-eat__rank">${index + 1}</span>
                    <span class="must-eat__body">
                        <strong>${this.escapeHtml(restaurant.name || '')}</strong>
                        <span class="restaurant-card__rating">⭐ ${this.escapeHtml(this.formatRating(restaurant.avg_rating))} · ${this.escapeHtml(String(restaurant.review_count || 0))} ${this.escapeHtml(this.labels.reviews)}</span>
                        <span class="restaurant-card__tags">${this.renderTags(restaurant.tags || [], 2)}</span>
                    </span>
                </button>
            `).join('');
        }

        renderCardMarkup(restaurant, index = 0) {
            const rating = Number(restaurant.avg_rating || 0);
            const distance = Number(restaurant.distance_km || 0);
            const distanceMarkup = distance > 0
                ? `<span>${this.escapeHtml(distance.toFixed(1))} km</span>`
                : '';

            return `
                <article class="food-card restaurant-card" data-restaurant-card data-restaurant-id="${this.escapeAttribute(restaurant.id)}" tabindex="0">
                    <img class="food-card__image restaurant-card__image" src="${this.escapeAttribute(restaurant.image_url || '')}" alt="${this.escapeAttribute(restaurant.name || '')}" loading="lazy" decoding="async">
                    <div class="food-card__body">
                        <div class="restaurant-card__header">
                            <span class="restaurant-card__rank">#${this.escapeHtml(String(index + 1))}</span>
                            <h3 class="food-card__title restaurant-card__name">${this.escapeHtml(restaurant.name || '')}</h3>
                            <span class="restaurant-card__rating">⭐ ${this.escapeHtml(this.formatRating(rating))}</span>
                        </div>
                        <div class="restaurant-card__meta">
                            <span>${this.escapeHtml(String(restaurant.review_count || 0))} ${this.escapeHtml(this.labels.reviews)}</span>
                            <span>${this.escapeHtml(restaurant.price_range || '—')}</span>
                            <span>📍 ${this.escapeHtml(restaurant.area || '')}</span>
                            ${distanceMarkup}
                        </div>
                        <div class="restaurant-card__tags">${this.renderTags(restaurant.tags || [])}</div>
                        <div class="restaurant-card__actions">
                            <a class="btn btn--outline btn--sm" data-detail-link href="/restaurant/${this.escapeAttribute(restaurant.id)}">${this.escapeHtml(this.labels.viewDetails)}</a>
                            <button
                                type="button"
                                class="btn btn--outline btn--sm"
                                data-feedback-open
                                data-feedback-context-type="restaurant"
                                data-feedback-restaurant-id="${this.escapeAttribute(restaurant.id)}"
                                data-feedback-context-label="${this.escapeAttribute(restaurant.name || '')}"
                            >${this.escapeHtml(this.labels.reportIssue)}</button>
                        </div>
                    </div>
                </article>
            `;
        }

        renderTags(tags, limit = 3) {
            return (Array.isArray(tags) ? tags : []).slice(0, limit).map((tag) => (
                `<span class="tag-pill tag-pill--${this.escapeAttribute(tag.slug || 'default')}">${this.escapeHtml(tag.name || '')}</span>`
            )).join('');
        }

        formatRating(value) {
            const rating = Number(value || 0);

            return rating.toFixed(2).replace(/\.?0+$/, '');
        }

        highlightCard(restaurantId) {
            const selectorValue = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(String(restaurantId))
                : String(restaurantId).replace(/"/g, '\\"');
            const card = this.cardsContainer.querySelector(`[data-restaurant-card][data-restaurant-id="${selectorValue}"]`);

            if (!card) {
                return;
            }

            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.remove('restaurant-card--highlighted');
            void card.offsetWidth;
            card.classList.add('restaurant-card--highlighted');

            window.setTimeout(() => {
                card.classList.remove('restaurant-card--highlighted');
            }, 1800);
        }

        syncAllUi() {
            this.syncDraftFilters();
            this.syncActiveSummary();
        }

        syncDraftFilters() {
            this.root.querySelectorAll('[data-filter-service]').forEach((button) => {
                const isActive = (button.dataset.filterService || 'all') === this.draftServiceFilter;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            this.root.querySelectorAll('[data-filter-category]').forEach((button) => {
                const isActive = (button.dataset.filterCategory || 'all') === this.draftCategory;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        syncActiveSummary() {
            if (!this.activeSummary) {
                return;
            }

            const serviceLabels = {
                all: this.labels.allRestaurants,
                'must-eat': this.labels.mustEat,
                'high-rated': this.labels.highRated,
                nearby: this.labels.nearby,
            };
            const categoryButton = this.root.querySelector(`[data-filter-category="${this.escapeSelector(this.activeCategory)}"]`);
            const categoryLabel = this.activeCategory === 'all'
                ? ''
                : ` · ${(categoryButton?.textContent || '').trim()}`;
            const searchLabel = this.searchTerm ? ` · "${this.searchTerm}"` : '';
            const locationLabel = this.locationSearch?.label ? ` · ${this.locationSearch.label}` : '';

            this.activeSummary.textContent = `${serviceLabels[this.activeServiceFilter] || this.labels.allRestaurants}${categoryLabel}${searchLabel}${locationLabel} · ${this.visibleRestaurants.length} ${this.labels.spotsSuffix}`;
        }

        showError(message) {
            if (!this.errorElement) {
                return;
            }

            this.errorElement.hidden = false;
            this.errorElement.textContent = message;
        }

        clearError() {
            if (!this.errorElement) {
                return;
            }

            this.errorElement.hidden = true;
            this.errorElement.textContent = '';
        }

        escapeSelector(value) {
            if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
                return CSS.escape(String(value));
            }

            return String(value).replace(/"/g, '\\"');
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

    window.RestaurantCatalog = {
        init(options) {
            return RestaurantCatalog.init(options);
        },
    };
})();
